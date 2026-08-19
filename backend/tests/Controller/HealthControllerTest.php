<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HealthControllerTest extends KernelTestCase
{
    private $em;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testDatabaseConnectionHealth(): void
    {
        $conn = $this->em->getConnection();
        $stmt = $conn->executeQuery('SELECT 1');
        $res = $stmt->fetchOne();

        $this->assertEquals(1, $res);
    }
}
