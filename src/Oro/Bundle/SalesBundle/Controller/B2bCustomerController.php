<?php

namespace Oro\Bundle\SalesBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Entity\Lead;
use Oro\Bundle\SalesBundle\Entity\Opportunity;
use Oro\Bundle\SalesBundle\Form\Handler\B2bCustomerHandler;
use Oro\Bundle\SalesBundle\Form\Type\B2bCustomerType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for B2bCustomer entity.
 */
#[Route(path: '/b2bcustomer')]
class B2bCustomerController extends AbstractController
{
    #[Route(
        path: '/{_format}',
        name: 'oro_sales_b2bcustomer_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroSales/B2bCustomer/index.html.twig')]
    #[AclAncestor('oro_sales_b2bcustomer_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => B2bCustomer::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_sales_b2bcustomer_view', requirements: ['id' => '\d+'])]
    #[Template('@OroSales/B2bCustomer/view.html.twig')]
    #[Acl(id: 'oro_sales_b2bcustomer_view', type: 'entity', class: B2bCustomer::class, permission: 'VIEW')]
    public function viewAction(B2bCustomer $customer): array
    {
        return [
            'entity' => $customer
        ];
    }

    #[Route(path: '/widget/info/{id}', name: 'oro_sales_b2bcustomer_widget_info', requirements: ['id' => '\d+'])]
    #[Template('@OroSales/B2bCustomer/info.html.twig')]
    #[AclAncestor('oro_sales_b2bcustomer_view')]
    public function infoAction(B2bCustomer $customer): array
    {
        return [
            'entity' => $customer
        ];
    }

    #[Route(
        path: '/widget/b2bcustomer-leads/{id}',
        name: 'oro_sales_b2bcustomer_widget_leads',
        requirements: ['id' => '\d+']
    )]
    #[Template('@OroSales/B2bCustomer/b2bCustomerLeads.html.twig')]
    #[AclAncestor('oro_sales_lead_view')]
    public function b2bCustomerLeadsAction(B2bCustomer $customer): array
    {
        return [
            'entity' => $customer
        ];
    }

    /**
     * Create b2bcustomer form
     */
    #[Route(path: '/create', name: 'oro_sales_b2bcustomer_create')]
    #[Template('@OroSales/B2bCustomer/update.html.twig')]
    #[Acl(id: 'oro_sales_b2bcustomer_create', type: 'entity', class: B2bCustomer::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new B2bCustomer());
    }

    protected function update(?B2bCustomer $entity = null): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $entity,
            $this->createForm(B2bCustomerType::class, $entity),
            $this->container->get(TranslatorInterface::class)->trans('oro.sales.controller.b2bcustomer.saved.message'),
            null,
            $this->container->get(B2bCustomerHandler::class)
        );
    }

    /**
     * Update user form
     */
    #[Route(
        path: '/update/{id}',
        name: 'oro_sales_b2bcustomer_update',
        requirements: ['id' => '\d+'],
        defaults: ['id' => 0]
    )]
    #[Template('@OroSales/B2bCustomer/update.html.twig')]
    #[Acl(id: 'oro_sales_b2bcustomer_update', type: 'entity', class: B2bCustomer::class, permission: 'EDIT')]
    public function updateAction(B2bCustomer $entity): array|RedirectResponse
    {
        return $this->update($entity);
    }

    #[Route(
        path: '/widget/b2bcustomer-opportunities/{id}',
        name: 'oro_sales_b2bcustomer_widget_opportunities',
        requirements: ['id' => '\d+']
    )]
    #[Template('@OroSales/B2bCustomer/b2bCustomerOpportunities.html.twig')]
    #[AclAncestor('oro_sales_opportunity_view')]
    public function b2bCustomerOpportunitiesAction(B2bCustomer $customer): array
    {
        return [
            'entity' => $customer
        ];
    }

    #[Route(
        path: '/widget/b2bcustomers-info/account/{accountId}/channel/{channelId}',
        name: 'oro_sales_widget_account_b2bcustomers_info',
        requirements: ['accountId' => '\d+', 'channelId' => '\d+']
    )]
    #[Template('@OroSales/B2bCustomer/accountCustomersInfo.html.twig')]
    #[AclAncestor('oro_sales_b2bcustomer_view')]
    public function accountCustomersInfoAction(
        #[MapEntity(id: 'accountId')]
        Account $account,
        #[MapEntity(id: 'channelId')]
        Channel $channel
    ): array {
        $customers = $this->container->get('doctrine')
            ->getRepository(B2bCustomer::class)
            ->findBy(['account' => $account, 'dataChannel' => $channel]);

        return ['account' => $account, 'customers' => $customers, 'channel' => $channel];
    }

    #[Route(
        path: '/widget/b2bcustomer-info/{id}/channel/{channelId}',
        name: 'oro_sales_widget_b2bcustomer_info',
        requirements: ['id' => '\d+', 'channelId' => '\d+']
    )]
    #[Template('@OroSales/B2bCustomer/customerInfo.html.twig')]
    #[AclAncestor('oro_sales_b2bcustomer_view')]
    public function customerInfoAction(
        B2bCustomer $customer,
        #[MapEntity(id: 'channelId')]
        Channel $channel
    ): array {
        return [
            'customer'             => $customer,
            'channel'              => $channel,
            'leadClassName'        => Lead::class,
            'opportunityClassName' => Opportunity::class
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                B2bCustomerHandler::class,
                UpdateHandlerFacade::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}
