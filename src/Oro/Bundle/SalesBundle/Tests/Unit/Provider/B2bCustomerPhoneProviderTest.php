<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Entity\B2bCustomerPhone;
use Oro\Bundle\SalesBundle\Provider\B2bCustomerPhoneProvider;

class B2bCustomerPhoneProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PhoneProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rootProvider;

    /** @var B2bCustomerPhoneProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->rootProvider = $this->createMock(PhoneProviderInterface::class);

        $this->provider = new B2bCustomerPhoneProvider();
        $this->provider->setRootProvider($this->rootProvider);
    }

    public function testGetPhoneNumber()
    {
        $entity = new B2bCustomer();
        $this->assertNull(
            $this->provider->getPhoneNumber($entity)
        );

        $phone1 = new B2bCustomerPhone('123-123');
        $entity->addPhone($phone1);
        $phone2 = new B2bCustomerPhone('456-456');
        $phone2->setPrimary(true);
        $entity->addPhone($phone2);
        $this->assertEquals(
            '456-456',
            $this->provider->getPhoneNumber($entity)
        );
    }

    public function testGetPhoneNumbers()
    {
        $entity = new B2bCustomer();

        $this->assertSame(
            [],
            $this->provider->getPhoneNumbers($entity)
        );
        $phone1 = new B2bCustomerPhone('123-123');
        $entity->addPhone($phone1);
        $phone2 = new B2bCustomerPhone('456-456');
        $phone2->setPrimary(true);
        $entity->addPhone($phone2);
        $this->assertSame(
            [
                ['123-123', $entity],
                ['456-456', $entity]
            ],
            $this->provider->getPhoneNumbers($entity)
        );
    }
}
