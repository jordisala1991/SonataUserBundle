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

namespace Sonata\UserBundle\DependencyInjection;

use Sonata\UserBundle\Admin\Entity\GroupAdmin;
use Sonata\UserBundle\Admin\Entity\UserAdmin;
use Sonata\UserBundle\Entity\BaseGroup;
use Sonata\UserBundle\Entity\BaseUser;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata_user');

        $supportedManagerTypes = ['orm', 'mongodb'];

        $rootNode
            ->children()
                ->booleanNode('security_acl')->defaultFalse()->end()
                ->arrayNode('table')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('user_group')->defaultValue('fos_user_user_group')->end()
                    ->end()
                ->end()
                ->scalarNode('impersonating_route')->end()
                ->arrayNode('impersonating')
                    ->children()
                        ->scalarNode('route')->defaultFalse()->end()
                        ->arrayNode('parameters')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('google_authenticator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('server')->cannotBeEmpty()->end()
                        ->scalarNode('enabled')->defaultFalse()->end()
                        ->arrayNode('ip_white_list')
                            ->prototype('scalar')->end()
                            ->defaultValue(['127.0.0.1'])
                            ->info('IPs for which 2FA will be skipped.')
                        ->end()
                        ->arrayNode('forced_for_role')
                            ->prototype('scalar')->end()
                            ->defaultValue(['ROLE_ADMIN'])
                            ->info('User roles for which 2FA is necessary.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('manager_type')
                    ->defaultValue('orm')
                    ->validate()
                        ->ifNotInArray($supportedManagerTypes)
                        ->thenInvalid('The manager type %s is not supported. Please choose one of '.json_encode($supportedManagerTypes))
                    ->end()
                ->end()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('group')->cannotBeEmpty()->defaultValue(BaseGroup::class)->end()
                        ->scalarNode('user')->cannotBeEmpty()->defaultValue(BaseUser::class)->end()
                    ->end()
                ->end()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('group')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(GroupAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue('SonataAdminBundle:CRUD')->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('SonataUserBundle')->end()
                            ->end()
                        ->end()
                        ->arrayNode('user')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(UserAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue('SonataAdminBundle:CRUD')->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('SonataUserBundle')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('profile')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('default_avatar')->defaultValue('bundles/sonatauser/default_avatar.png')->end()
                        ->arrayNode('dashboard')
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('group')
                            ->fixXmlConfig('block')
                            ->children()
                                ->arrayNode('groups')
                                    ->useAttributeAsKey('id')
                                    ->prototype('array')
                                    ->fixXmlConfig('item')
                                    ->fixXmlConfig('item_add')
                                    ->children()
                                        ->scalarNode('label')->end()
                                        ->scalarNode('label_catalogue')->end()
                                        ->arrayNode('items')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->arrayNode('item_adds')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->arrayNode('roles')
                                            ->prototype('scalar')->defaultValue([])->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('blocks')
                                ->defaultValue([['position' => 'left', 'settings' => ['content' => '<h2>Welcome!</h2> This is a sample user profile dashboard, feel free to override it in the configuration!'], 'type' => 'sonata.block.service.text']])
                                ->prototype('array')
                                    ->fixXmlConfig('setting')
                                        ->children()
                                            ->scalarNode('type')->cannotBeEmpty()->end()
                                            ->arrayNode('settings')
                                                ->useAttributeAsKey('id')
                                                ->prototype('variable')->defaultValue([])->end()
                                            ->end()
                                            ->scalarNode('position')->defaultValue('right')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('form')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('type')->defaultValue('sonata_user_profile')->end()
                                ->scalarNode('name')->defaultValue('sonata_user_profile_form')->cannotBeEmpty()->end()
                                ->arrayNode('validation_groups')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(['Profile', 'Default'])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('menu')
                            ->prototype('array')
                                ->addDefaultsIfNotSet()
                                ->cannotBeEmpty()
                                ->children()
                                    ->scalarNode('route')->cannotBeEmpty()->end()
                                    ->arrayNode('route_parameters')
                                        ->defaultValue([])
                                        ->prototype('array')->end()
                                    ->end()
                                    ->scalarNode('label')->cannotBeEmpty()->end()
                                    ->scalarNode('domain')->defaultValue('messages')->end()
                                ->end()
                            ->end()
                            ->defaultValue($this->getProfileMenuDefaultValues())
                        ->end()
                        ->arrayNode('register')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('form')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('type')->defaultValue('sonata_user_registration')->end()
                                        ->scalarNode('name')->defaultValue('sonata_user_registration_form')->cannotBeEmpty()->end()
                                        ->arrayNode('validation_groups')
                                            ->prototype('scalar')->end()
                                            ->defaultValue(['Registration', 'Default'])
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('confirm')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('redirect')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('route')->defaultValue('sonata_user_profile_show')->end()
                                                ->arrayNode('route_parameters')
                                                    ->defaultValue([])
                                                    ->prototype('array')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Returns default values for profile menu (to avoid BC Break).
     *
     * @return array
     */
    protected function getProfileMenuDefaultValues()
    {
        return [
            [
                'route' => 'sonata_user_profile_edit',
                'label' => 'link_edit_profile',
                'domain' => 'SonataUserBundle',
                'route_parameters' => [],
            ],
            [
                'route' => 'sonata_user_profile_edit_password',
                'label' => 'link_edit_authentication',
                'domain' => 'SonataUserBundle',
                'route_parameters' => [],
            ],
        ];
    }
}
