<?php

namespace App\Command;


use App\Entity\LeaveType;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed',
    description: 'Seeds the database with default departments, roles, permissions, leave types, and an administrator account.',
)]
class SeedDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Seed Permissions
        $permissionsData = [
            'MANAGE_USERS' => 'Manage system users',
            'MANAGE_DEPARTMENTS' => 'Manage company departments',
            'MANAGE_ROLES' => 'Manage roles and permission mapping',
            'VIEW_ALL_TICKETS' => 'View all tickets in the system',
            'ASSIGN_TICKETS' => 'Assign tickets to technicians',
            'REASSIGN_TICKETS' => 'Reassign tickets to different technicians',
            'CLOSE_TICKETS' => 'Force close any tickets',
            'CONFIGURE_APP' => 'Configure system-wide settings',
        ];

        $permissions = [];
        foreach ($permissionsData as $name => $description) {
            $existing = $this->entityManager->getRepository(Permission::class)->findOneBy(['name' => $name]);
            if (!$existing) {
                $permission = new Permission();
                $permission->setName($name);
                $permission->setDescription($description);
                $this->entityManager->persist($permission);
                $permissions[$name] = $permission;
                $io->writeln("Created permission: $name");
            } else {
                $permissions[$name] = $existing;
            }
        }

        // 2. Seed Roles
        $rolesData = [
            'ROLE_ADMIN' => ['displayName' => 'Administrator', 'perms' => array_keys($permissionsData)],
            'ROLE_EMPLOYEE' => ['displayName' => 'Employee', 'perms' => []],
            'ROLE_IT_TECH' => ['displayName' => 'IT Technician', 'perms' => ['VIEW_ALL_TICKETS']],
            'ROLE_MAINTENANCE_TECH' => ['displayName' => 'Maintenance Technician', 'perms' => ['VIEW_ALL_TICKETS']],
            'ROLE_HR' => ['displayName' => 'HR Manager', 'perms' => ['VIEW_ALL_TICKETS']],
        ];

        $roles = [];
        foreach ($rolesData as $name => $info) {
            $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $name]);
            if (!$role) {
                $role = new Role();
                $role->setName($name);
                $role->setDisplayName($info['displayName']);
                $this->entityManager->persist($role);
                $io->writeln("Created role: $name");
            }
            // Sync permissions
            foreach ($info['perms'] as $permName) {
                if (isset($permissions[$permName])) {
                    $role->addPermission($permissions[$permName]);
                }
            }
            $roles[$name] = $role;
        }
        $this->entityManager->flush();



        // 4. Seed Leave Types
        $leaveTypesData = [
            'ANNUAL' => 'Annual Leave',
            'SICK' => 'Sick Leave',
            'PARENTAL' => 'Parental Leave',
            'UNPAID' => 'Unpaid Leave',
        ];

        foreach ($leaveTypesData as $code => $name) {
            $existing = $this->entityManager->getRepository(LeaveType::class)->findOneBy(['code' => $code]);
            if (!$existing) {
                $lt = new LeaveType();
                $lt->setCode($code);
                $lt->setName($name);
                $this->entityManager->persist($lt);
                $io->writeln("Created leave type: $name");
            }
        }
        $this->entityManager->flush();

        // 5. Seed Admin User
        $adminEmail = 'admin@example.com';
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail($adminEmail);
            $adminUser->setFirstName('System');
            $adminUser->setLastName('Administrator');
            $adminUser->setRoleEntity($roles['ROLE_ADMIN']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'admin123');
            $adminUser->setPassword($hashedPassword);
            $adminUser->setPlainPassword('admin123');
            
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
            $io->success("Created admin user: $adminEmail / password: admin123");
        } else {
            $adminUser->setPlainPassword('admin123');
            $this->entityManager->flush();
            $io->info("Admin user already exists. Updated plain password.");
        }

        // 6. Seed some test accounts for each role
        $testUsers = [
            ['email' => 'employee@example.com', 'first' => 'John', 'last' => 'Doe', 'role' => 'ROLE_EMPLOYEE', 'pass' => 'pass123'],
            ['email' => 'tech.it@example.com', 'first' => 'Alex', 'last' => 'IT', 'role' => 'ROLE_IT_TECH', 'pass' => 'pass123'],
            ['email' => 'tech.maint@example.com', 'first' => 'Bob', 'last' => 'Maintenance', 'role' => 'ROLE_MAINTENANCE_TECH', 'pass' => 'pass123'],
            ['email' => 'hr@example.com', 'first' => 'Sarah', 'last' => 'HR', 'role' => 'ROLE_HR', 'pass' => 'pass123'],
        ];

        foreach ($testUsers as $u) {
            $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $u['email']]);
            if (!$existing) {
                $user = new User();
                $user->setEmail($u['email']);
                $user->setFirstName($u['first']);
                $user->setLastName($u['last']);
                $user->setRoleEntity($roles[$u['role']]);
                $user->setPassword($this->passwordHasher->hashPassword($user, $u['pass']));
                $user->setPlainPassword($u['pass']);
                
                $this->entityManager->persist($user);
                $io->writeln("Created test user: {$u['email']}");
            } else {
                $existing->setPlainPassword($u['pass']);
            }
        }
        $this->entityManager->flush();

        $io->success('Database seeding completed successfully!');
        return Command::SUCCESS;
    }
}
