<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Entity\B2bCustomerEmail;
use Oro\Bundle\SalesBundle\Handler\B2bCustomerEmailDeleteHandlerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class B2bCustomerEmailDeleteHandlerExtensionTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TranslatorInterface&MockObject $translator;
    private B2bCustomerEmailDeleteHandlerExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new B2bCustomerEmailDeleteHandlerExtension(
            $this->authorizationChecker,
            $this->translator
        );
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNoOwner(): void
    {
        $customerEmail = new B2bCustomerEmail();

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerEmail);
    }

    public function testAssertDeleteGrantedWhenAccessGranted(): void
    {
        $customerEmail = new B2bCustomerEmail();
        $customer = new B2bCustomer();
        $customerEmail->setOwner($customer);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerEmail);
    }

    public function testAssertDeleteGrantedWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $customerEmail = new B2bCustomerEmail();
        $customer = new B2bCustomer();
        $customerEmail->setOwner($customer);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(false);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerEmail);
    }

    public function testAssertDeleteGrantedWhenPrimaryEmailIsDeletedAndThereIsOtherEmails(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: translated exception message.');

        $customerEmail = new B2bCustomerEmail();
        $customer = new B2bCustomer();
        $customerEmail->setOwner($customer);

        $customerEmail->setPrimary(true);
        $customer->addEmail($customerEmail);
        $customer->addEmail(new B2bCustomerEmail());

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.sales.validation.b2bcustomer.emails.delete.more_one', [], 'validators')
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($customerEmail);
    }

    public function testAssertDeleteGrantedWhenPrimaryEmailIsDeletedIfThereIsNoOtherEmails(): void
    {
        $customerEmail = new B2bCustomerEmail();
        $customer = new B2bCustomer();
        $customerEmail->setOwner($customer);

        $customerEmail->setPrimary(true);
        $customer->addEmail($customerEmail);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerEmail);
    }

    public function testAssertDeleteGrantedWhenNotPrimaryEmailIsDeleted(): void
    {
        $customerEmail = new B2bCustomerEmail();
        $customer = new B2bCustomer();
        $customerEmail->setOwner($customer);

        $customer->addEmail($customerEmail);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerEmail);
    }
}
