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
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserProfileController extends Controller
{
    private $profileFormFactory;
    private $passwordFormFactory;
    private $userManager;

    public function __construct(
        FactoryInterface $profileFormFactory,
        FactoryInterface $passwordFormFactory,
        UserManagerInterface $userManager
    ) {
        $this->profileFormFactory = $profileFormFactory;
        $this->passwordFormFactory = $passwordFormFactory;
        $this->userManager = $userManager;
    }

    /**
     * @throws AccessDeniedException
     */
    public function showAction(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('@SonataUser/User/Profile/show.html.twig', [
            'user' => $user,
            'blocks' => $this->container->getParameter('sonata.user.configuration.profile_blocks'),
        ]);
    }

    /**
     * @throws AccessDeniedException
     *
     * @return Response|RedirectResponse
     */
    public function editProfileAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->profileFormFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->updateUser($user);
            $this->addFlash('sonata_user_success', 'profile.flash.updated');

            return $this->redirect($this->generateUrl('sonata_user_profile_show'));
        }

        return $this->render('@SonataUser/User/Profile/edit_profile.html.twig', [
            'form' => $form->createView(),
            'breadcrumb_context' => 'user_profile',
        ]);
    }

    /**
     * @throws AccessDeniedException
     *
     * @return Response|RedirectResponse
     */
    public function editPasswordAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->passwordFormFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->updateUser($user);
            $this->addFlash('fos_user_success', 'change_password.flash.success');

            return $this->redirect($this->generateUrl('sonata_user_profile_show'));
        }

        return $this->render('@SonataUser/User/Profile/edit_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
