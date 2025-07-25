<?php

namespace Oro\Bundle\CaseBundle\Controller;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Form\Handler\CaseEntityHandler;
use Oro\Bundle\CaseBundle\Model\CaseEntityManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Cases.
 */
class CaseController extends AbstractController
{
    #[Route(name: 'oro_case_index')]
    #[Template]
    #[AclAncestor('oro_case_view')]
    public function indexAction()
    {
        return [];
    }

    #[Route(path: '/view/{id}', name: 'oro_case_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_case_view')]
    public function viewAction(CaseEntity $case)
    {
        return [
            'entity' => $case
        ];
    }

    #[Route(path: '/widget/account-cases/{id}', name: 'oro_case_account_widget_cases', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_case_view')]
    public function accountCasesAction(Account $account)
    {
        return [
            'account' => $account
        ];
    }

    #[Route(path: '/widget/contact-cases/{id}', name: 'oro_case_contact_widget_cases', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_case_view')]
    public function contactCasesAction(Contact $contact)
    {
        return [
            'contact' => $contact
        ];
    }

    /**
     * Create case form
     */
    #[Route(path: '/create', name: 'oro_case_create')]
    #[Template('@OroCase/Case/update.html.twig')]
    #[AclAncestor('oro_case_create')]
    public function createAction(Request $request)
    {
        $case = $this->container->get(CaseEntityManager::class)->createCase();

        return $this->update($case, $request);
    }

    #[Route(path: '/update/{id}', name: 'oro_case_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_case_update')]
    public function updateAction(CaseEntity $case, Request $request)
    {
        return $this->update($case, $request);
    }

    /**
     * @param CaseEntity $case
     * @param Request $request
     * @return array
     */
    protected function update(CaseEntity $case, Request $request)
    {
        if ($this->container->get(CaseEntityHandler::class)->process($case)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.case.message.saved')
            );

            return $this->container->get(Router::class)->redirect($case);
        }

        return [
            'entity' => $case,
            'form'   => $this->container->get('oro_case.form.entity')->createView()
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                CaseEntityManager::class,
                CaseEntityHandler::class,
                'oro_case.form.entity' => Form::class,
            ]
        );
    }
}
