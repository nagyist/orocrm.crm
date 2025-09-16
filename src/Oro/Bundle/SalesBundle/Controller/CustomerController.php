<?php

namespace Oro\Bundle\SalesBundle\Controller;

use Oro\Bundle\DataGridBundle\Provider\MultiGridProvider;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SalesBundle\Provider\Customer\AccountConfigProvider;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides grid dialog action
 */
#[Route(path: '/customer')]
class CustomerController extends AbstractController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EntityRoutingHelper::class,
            'oro_sales.customer.account_config_provider' => AccountConfigProvider::class,
            MultiGridProvider::class,
            EntityAliasResolver::class
        ]);
    }

    /**
     *
     * @param string $entityClass
     *
     * @return array
     */
    #[Route(path: '/customer/grid-dialog/{entityClass}', name: 'oro_sales_customer_grid_dialog')]
    #[Template('@OroDataGrid/Grid/dialog/multi.html.twig')]
    public function gridDialogAction($entityClass)
    {
        $resolvedClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityClass);
        $entityClassAlias = $this->container->get(EntityAliasResolver::class)
            ->getPluralAlias($resolvedClass);
        $entityTargets = $this->container->get(MultiGridProvider::class)->getEntitiesData(
            $this->container->get('oro_sales.customer.account_config_provider')->getCustomerClasses()
        );

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $params = [
            'params' => $request->get('params', [])
        ];
        if (isset($entityTargets[0]['gridName'], $entityTargets[0]['className'])) {
            $params = array_merge_recursive(
                $params,
                [
                    'gridName' => $entityTargets[0]['gridName'],
                    'params' => [
                        'entity_class' => $entityTargets[0]['className']
                    ]
                ]
            );
        }

        return [
            'gridWidgetName'         => 'customer-multi-grid-widget',
            'dialogWidgetName'       => 'customer-dialog',
            'params'                 => $params,
            'sourceEntityClassAlias' => $entityClassAlias,
            'entityTargets'          => $entityTargets
        ];
    }
}
