<?php

namespace Oro\Bundle\ContactBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Form\Handler\ContactHandler;
use Oro\Bundle\ContactBundle\Form\Type\ContactType;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for Contact entity.
 */
class ContactController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_contact_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_contact_view', type: 'entity', class: Contact::class, permission: 'VIEW')]
    public function viewAction(Contact $contact): array
    {
        return [
            'entity' => $contact,
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_contact_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_contact_view')]
    public function infoAction(Request $request, Contact $contact): array|RedirectResponse
    {
        if (!$request->get('_wid')) {
            return $this->redirect(
                $this->container->get(RouterInterface::class)->generate('oro_contact_view', ['id' => $contact->getId()])
            );
        }

        return [
            'entity'  => $contact
        ];
    }

    /**
     * Create contact form
     */
    #[Route(path: '/create', name: 'oro_contact_create')]
    #[Template('@OroContact/Contact/update.html.twig')]
    #[Acl(id: 'oro_contact_create', type: 'entity', class: Contact::class, permission: 'CREATE')]
    public function createAction(Request $request): array|RedirectResponse
    {
        // add predefined account to contact
        $contact = null;
        $entityClass = $request->get('entityClass');
        if ($entityClass) {
            $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityClass);
            $entityId = $request->get('entityId');
            if ($entityId && $entityClass === Account::class) {
                $repository = $this->container->get('doctrine')->getRepository($entityClass);
                /** @var Account $account */
                $account = $repository->find($entityId);
                if ($account) {
                    /** @var Contact $contact */
                    $contact = $this->getManager()->createEntity();
                    $contact->addAccount($account);
                } else {
                    throw new NotFoundHttpException(sprintf('Account with ID %s is not found', $entityId));
                }
            }
        }

        return $this->update($contact);
    }

    /**
     * Update user form
     */
    #[Route(path: '/update/{id}', name: 'oro_contact_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_contact_update', type: 'entity', class: Contact::class, permission: 'EDIT')]
    public function updateAction(Contact $entity): array|RedirectResponse
    {
        return $this->update($entity);
    }

    #[Route(
        path: '/{_format}',
        name: 'oro_contact_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[AclAncestor('oro_contact_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => Contact::class
        ];
    }

    protected function getManager(): ApiEntityManager
    {
        return $this->container->get(ApiEntityManager::class);
    }

    protected function update(?Contact $entity = null): array|RedirectResponse
    {
        if (!$entity) {
            $entity = $this->getManager()->createEntity();
        }

        return $this->container->get(UpdateHandlerFacade::class)->update(
            $entity,
            $this->container->get(ContactType::class),
            $this->container->get(TranslatorInterface::class)->trans('oro.contact.controller.contact.saved.message'),
            null,
            $this->container->get(ContactHandler::class)
        );
    }

    #[Route(path: '/widget/account-contacts/{id}', name: 'oro_account_widget_contacts', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_contact_view')]
    public function accountContactsAction(Account $account): array
    {
        $defaultContact = $account->getDefaultContact();
        $contacts = $account->getContacts();
        $contactsWithoutDefault = array();

        if (empty($defaultContact)) {
            $contactsWithoutDefault = $contacts->toArray();
        } else {
            /** @var Contact $contact */
            foreach ($contacts as $contact) {
                if ($contact->getId() == $defaultContact->getId()) {
                    continue;
                }
                $contactsWithoutDefault[] = $contact;
            }
        }

        /**
         * Compare contacts to sort them alphabetically
         *
         * @param Contact $firstContact
         * @param Contact $secondContact
         * @return int
         */
        $compareFunction = function ($firstContact, $secondContact) {
            $first = $firstContact->getLastName() . $firstContact->getFirstName() . $firstContact->getMiddleName();
            $second = $secondContact->getLastName() . $secondContact->getFirstName() . $secondContact->getMiddleName();
            return strnatcasecmp($first, $second);
        };

        usort($contactsWithoutDefault, $compareFunction);

        return array(
            'entity'                 => $account,
            'defaultContact'         => $defaultContact,
            'contactsWithoutDefault' => $contactsWithoutDefault
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EntityRoutingHelper::class,
                ApiEntityManager::class,
                ContactType::class,
                TranslatorInterface::class,
                ContactHandler::class,
                UpdateHandlerFacade::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}
