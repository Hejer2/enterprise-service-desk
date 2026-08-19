<?php

namespace App\Tests\Controller;

use App\Entity\KnowledgeArticle;
use App\Entity\KnowledgeCategory;
use App\Entity\User;
use App\Entity\Role;
use App\Service\KnowledgeBaseService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3bTest extends KernelTestCase
{
    private $em;
    private $kbService;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        
        $articleRepo = $this->em->getRepository(KnowledgeArticle::class);
        $categoryRepo = $this->em->getRepository(KnowledgeCategory::class);
        $feedbackRepo = $this->em->getRepository(\App\Entity\KnowledgeArticleFeedback::class);

        $this->kbService = new KnowledgeBaseService(
            $this->em,
            $articleRepo,
            $categoryRepo,
            $feedbackRepo
        );
    }

    private function getOrCreateUser(string $email, string $roleName): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $role = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if (!$role) {
                $role = new Role();
                $role->setName($roleName);
                $role->setDescription($roleName);
                $this->em->persist($role);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName(ucfirst(explode('@', $email)[0]));
            $user->setLastName('User');
            $user->setPassword('password123');
            $user->setRoleEntity($role);

            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }

    public function testKnowledgeArticleCreationFeedbackAndUniqueness(): void
    {
        $admin = $this->getOrCreateUser('admin_kb@test.com', 'ROLE_ADMIN');
        $employee = $this->getOrCreateUser('emp_kb@test.com', 'ROLE_EMPLOYEE');

        $cat = new KnowledgeCategory();
        $cat->setName('Test KB Cat ' . uniqid());
        $this->em->persist($cat);
        $this->em->flush();

        $article = new KnowledgeArticle();
        $article->setTitle('How to configure VPN ' . uniqid());
        $article->setCategory($cat);
        $article->setExcerpt('VPN configuration guide');
        $article->setContent('Open VPN client and type server IP.');
        $article->setStatus('PUBLISHED');
        $article->setCreatedBy($admin);

        $this->em->persist($article);
        $this->em->flush();

        // 1. Submit feedback
        $res1 = $this->kbService->submitFeedback($article, $employee, true);
        $this->assertTrue($res1['success']);
        $this->assertFalse($res1['alreadySubmitted']);
        $this->assertEquals(1, $article->getHelpfulCount());

        // 2. Re-submit feedback (Idempotency test)
        $res2 = $this->kbService->submitFeedback($article, $employee, true);
        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['alreadySubmitted']);
        $this->assertEquals(1, $article->getHelpfulCount());
    }
}
