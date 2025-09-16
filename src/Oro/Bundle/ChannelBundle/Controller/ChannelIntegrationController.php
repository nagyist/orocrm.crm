<?php

namespace Oro\Bundle\ChannelBundle\Controller;

use Oro\Bundle\ChannelBundle\Form\Handler\ChannelIntegrationHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Channel Integrations.
 */
#[Route(path: '/integration')]
class ChannelIntegrationController extends AbstractController
{
    #[Route(
        path: '/create/{type}/{channelName}',
        requirements: ['type' => '\w+'],
        name: 'oro_channel_integration_create'
    )]
    #[Template('@OroChannel/ChannelIntegration/update.html.twig')]
    #[AclAncestor('oro_integration_create')]
    public function createAction($type, $channelName = null)
    {
        $translator      = $this->container->get(TranslatorInterface::class);
        $integrationName = urldecode($channelName) . ' ' . $translator->trans('oro.channel.data_source.label');
        $integration     = new Integration();
        $integration->setType(urldecode($type));
        $integration->setName(trim($integrationName));

        return $this->update($integration);
    }

    #[Route(path: '/update/{id}', requirements: ['id' => '\d+'], name: 'oro_channel_integration_update')]
    #[Template('@OroChannel/ChannelIntegration/update.html.twig')]
    #[AclAncestor('oro_integration_update')]
    public function updateAction(Integration $integration)
    {
        return $this->update($integration);
    }

    /**
     * @param Integration $integration
     *
     * @return array
     */
    protected function update(Integration $integration)
    {
        $handler = $this->container->get(ChannelIntegrationHandler::class);

        $data = null;
        if ($handler->process($integration)) {
            $data = $handler->getFormSubmittedData();
        }

        return [
            'form'        => $handler->getFormView(),
            'isSubmitted' => null !== $data,
            'savedId'     => $data
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                ChannelIntegrationHandler::class,
            ]
        );
    }
}
