<?php

namespace Tests\Unit;

use App\Licensing\ProductionDomain;
use PHPUnit\Framework\TestCase;

class ProductionDomainTest extends TestCase
{
    public function test_valid_hostname_is_canonicalized(): void
    {
        $this->assertSame(
            'company-a.test',
            ProductionDomain::normalize(' Company-A.Test ')
        );

        $this->assertSame(
            'app.company-a.test',
            ProductionDomain::normalize('APP.Company-A.Test')
        );
    }

    public function test_invalid_production_domains_are_rejected(): void
    {
        $invalidDomains = [
            '',
            'localhost',
            'https://company-a.test',
            'company-a.test:443',
            'company-a.test/path',
            'company-a.test?foo=bar',
            'company-a.test#fragment',
            '*.company-a.test',
            'bad_domain.test',
            '-bad.test',
            'bad-.test',
            'bad..test',
            'company-a.test.',
            '127.0.0.1',
            '::1',
            'company a.test',
        ];

        foreach ($invalidDomains as $domain) {
            $this->assertNull(
                ProductionDomain::normalize($domain),
                "Expected [{$domain}] to be rejected."
            );
        }
    }

    public function test_dns_label_longer_than_63_characters_is_rejected(): void
    {
        $domain = str_repeat('a', 64).'.test';

        $this->assertNull(ProductionDomain::normalize($domain));
    }

    public function test_hostname_longer_than_253_characters_is_rejected(): void
    {
        $domain = str_repeat('a', 63).'.'
            .str_repeat('b', 63).'.'
            .str_repeat('c', 63).'.'
            .str_repeat('d', 62).'.test';

        $this->assertGreaterThan(253, strlen($domain));
        $this->assertNull(ProductionDomain::normalize($domain));
    }
}
