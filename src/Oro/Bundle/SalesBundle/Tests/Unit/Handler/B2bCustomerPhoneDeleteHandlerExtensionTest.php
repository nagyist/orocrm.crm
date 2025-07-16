<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Entity\B2bCustomerPhone;
use Oro\Bundle\SalesBundle\Handler\B2bCustomerPhoneDeleteHandlerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class B2bCustomerPhoneDeleteHandlerExtensionTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TranslatorInterface&MockObject $translator;
    private B2bCustomerPhoneDeleteHandlerExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new B2bCustomerPhoneDeleteHandlerExtension(
            $this->authorizationChecker,
            $this->translator
        );
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNoOwner(): void
    {
        $customerPhone = new B2bCustomerPhone();

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerPhone);
    }

    public function testAssertDeleteGrantedWhenAccessGranted(): void
    {
        $customerPhone = new B2bCustomerPhone();
        $customer = new B2bCustomer();
        $customerPhone->setOwner($customer);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerPhone);
    }

    public function testAssertDeleteGrantedWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $customerPhone = new B2bCustomerPhone();
        $customer = new B2bCustomer();
        $customerPhone->setOwner($customer);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(false);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerPhone);
    }

    public function testAssertDeleteGrantedWhenPrimaryPhoneIsDeletedAndThereIsOtherPhones(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: translated exception message.');

        $customerPhone = new B2bCustomerPhone();
        $customer = new B2bCustomer();
        $customerPhone->setOwner($customer);

        $customerPhone->setPrimary(true);
        $customer->addPhone($customerPhone);
        $customer->addPhone(new B2bCustomerPhone());

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.sales.validation.b2bcustomer.phones.delete.more_one', [], 'validators')
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($customerPhone);
    }

    public function testAssertDeleteGrantedWhenPrimaryPhoneIsDeletedIfThereIsNoOtherPhones(): void
    {
        $customerPhone = new B2bCustomerPhone();
        $customer = new B2bCustomer();
        $customerPhone->setOwner($customer);

        $customerPhone->setPrimary(true);
        $customer->addPhone($customerPhone);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerPhone);
    }

    public function testAssertDeleteGrantedWhenNotPrimaryPhoneIsDeleted(): void
    {
        $customerPhone = new B2bCustomerPhone();
        $customer = new B2bCustomer();
        $customerPhone->setOwner($customer);

        $customer->addPhone($customerPhone);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($customer))
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method('trans');

        $this->extension->assertDeleteGranted($customerPhone);
    }
}
