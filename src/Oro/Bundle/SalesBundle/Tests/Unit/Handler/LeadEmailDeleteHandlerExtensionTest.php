<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\SalesBundle\Entity\Lead;
use Oro\Bundle\SalesBundle\Entity\LeadEmail;
use Oro\Bundle\SalesBundle\Handler\LeadEmailDeleteHandlerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadEmailDeleteHandlerExtensionTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TranslatorInterface&MockObject $translator;
    private LeadEmailDeleteHandlerExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new LeadEmailDeleteHandlerExtension(
            $this->authorizationChecker,
            $this->translator
        );
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNoOwner(): void
    {
        $leadEmail = new LeadEmail();

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($leadEmail);
    }

    public function testAssertDeleteGrantedWhenAccessGranted(): void
    {
        $leadEmail = new LeadEmail();
        $lead = new Lead();
        $leadEmail->setOwner($lead);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($lead))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($leadEmail);
    }

    public function testAssertDeleteGrantedWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $leadEmail = new LeadEmail();
        $lead = new Lead();
        $leadEmail->setOwner($lead);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($lead))
            ->willReturn(false);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($leadEmail);
    }

    public function testAssertDeleteGrantedWhenPrimaryEmailIsDeletedAndThereIsOtherEmails(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: translated exception message.');

        $leadEmail = new LeadEmail();
        $lead = new Lead();
        $leadEmail->setOwner($lead);

        $leadEmail->setPrimary(true);
        $lead->addEmail($leadEmail);
        $lead->addEmail(new LeadEmail());

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($lead))
            ->willReturn(true);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.sales.validation.lead.emails.delete.more_one', [], 'validators')
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($leadEmail);
    }

    public function testAssertDeleteGrantedWhenPrimaryEmailIsDeletedIfThereIsNoOtherEmails(): void
    {
        $leadEmail = new LeadEmail();
        $lead = new Lead();
        $leadEmail->setOwner($lead);

        $leadEmail->setPrimary(true);
        $lead->addEmail($leadEmail);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($lead))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($leadEmail);
    }

    public function testAssertDeleteGrantedWhenNotPrimaryEmailIsDeleted(): void
    {
        $leadEmail = new LeadEmail();
        $lead = new Lead();
        $leadEmail->setOwner($lead);

        $lead->addEmail($leadEmail);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($lead))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($leadEmail);
    }
}
