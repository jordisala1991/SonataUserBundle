<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Controller;

use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class UserResettingController extends Controller
{
    private $formFactory;
    private $userManager;
    private $tokenGenerator;
    private $mailer;
    private $retryTtl;
    private $tokenTtl;

    public function __construct(
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer,
        int $retryTtl,
        int $tokenTtl
    ) {
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->retryTtl = $retryTtl;
        $this->tokenTtl = $tokenTtl;
    }

    /**
     * @return RedirectResponse|Response
     */
    public function requestAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('sonata_user_profile_show'));
        }

        return $this->render('@SonataUser/User/Resetting/request.html.twig');
    }

    /**
     * @return RedirectResponse|Response
     */
    public function sendEmailAction(Request $request)
    {
        $username = $request->request->get('username');

        $user = $this->userManager->findUserByUsernameOrEmail($username);

        if (null !== $user && !$user->isPasswordRequestNonExpired($this->retryTtl)) {
            if (!$user->isAccountNonLocked()) {
                return new RedirectResponse($this->get('router')->generate('sonata_user_admin_resetting_request'));
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }

            $this->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $userManager->updateUser($user);
        }

        return new RedirectResponse($this->generateUrl('sonata_user_resetting_check_email', [
            'username' => $username,
        ]));
    }

    /**
     * @return RedirectResponse|Response
     */
    public function checkEmailAction(Request $request)
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('sonata_user_resetting_request'));
        }

        return $this->render('@SonataUser/User/Resetting/checkEmail.html.twig', [
            'tokenLifetime' => ceil($this->retryTtl / 3600),
        ]);
    }

    /**
     * @return RedirectResponse|Response
     */
    public function resetAction(Request $request, string $token)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->get('router')->generate('sonata_user_profile_show'));
        }

        $user = $this->userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->tokenTtl)) {
            return new RedirectResponse($this->generateUrl('sonata_user_resetting_request'));
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $this->addFlash('success', 'resetting.flash.success');
            $response = new RedirectResponse($this->generateUrl('sonata_user_profile_show'));

            try {
                $firewallName = $this->container->getParameter('fos_user.firewall_name');
                $loginManager->logInUser($firewallName, $user, $response);
                $user->setLastLogin(new \DateTime());
            } catch (AccountStatusException $ex) {
                // We simply do not authenticate users which do not pass the user
                // checker (not enabled, expired, etc.).
            }

            $userManager->updateUser($user);

            return $response;
        }

        return $this->render('@SonataUser/User/Resetting/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    private function sendResettingEmailMessage(UserInterface $user): void
    {
        $url = $this->generateUrl('sonata_user_resetting_reset', [
            'token' => $user->getConfirmationToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $rendered = $this->renderView($this->container->getParameter('fos_user.resetting.email.template'), [
            'user' => $user,
            'confirmationUrl' => $url,
        ]);

        // Render the email, use the first line as the subject, and the rest as the body
        $renderedLines = explode(PHP_EOL, trim($rendered));
        $subject = array_shift($renderedLines);
        $body = implode(PHP_EOL, $renderedLines);
        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom($this->container->getParameter('fos_user.resetting.email.from_email'))
            ->setTo((string) $user->getEmail())
            ->setBody($body);
        $this->get('mailer')->send($message);
    }
}
