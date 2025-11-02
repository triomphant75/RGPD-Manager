<?php

namespace App\Tests\Service;

use App\Service\DataBreachService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataBreachServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testAssessRiskRules()
    {
        $svc = new DataBreachService($this->em);

        [$risk, $sev, $notify] = $svc->assessRisk([
            'types' => [],
            'count' => 0,
            'encrypted' => true,
            'publiclyExposed' => false,
        ]);
        $this->assertSame('none', $risk);
        $this->assertSame('low', $sev);
        $this->assertFalse($notify);

        [$risk, $sev, $notify] = $svc->assessRisk([
            'types' => ['health'],
            'count' => 100,
            'encrypted' => false,
            'publiclyExposed' => false,
        ]);
        $this->assertSame('low', $risk);
        $this->assertSame('medium', $sev);
        $this->assertFalse($notify, 'Low risk should not require notification');

        [$risk, $sev, $notify] = $svc->assessRisk([
            'types' => ['financial'],
            'count' => 6000,
            'encrypted' => false,
            'publiclyExposed' => false,
        ]);
        $this->assertSame('medium', $risk);
        $this->assertSame('high', $sev);
        $this->assertTrue($notify);

        [$risk, $sev, $notify] = $svc->assessRisk([
            'types' => ['credentials'],
            'count' => 50,
            'encrypted' => false,
            'publiclyExposed' => true,
        ]);
        $this->assertSame('high', $risk);
        $this->assertSame('critical', $sev);
        $this->assertTrue($notify);
    }
}
