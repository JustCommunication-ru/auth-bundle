<?php

namespace JustCommunication\AuthBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use JustCommunication\AuthBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserCommand extends Command
{

    public function __construct(private UserPasswordHasherInterface $passwordHasher, private EntityManagerInterface $em)
    {
        parent::__construct();

    }

    protected function configure()
    {
        $this
            ->setName('jc:auth')
            ->setDescription('Support command class for auth')
            ->setHelp('')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Action. Make admin user row')
        ;

    }


    private function askName($input, $output, $helper){
        $question_name = new Question('Enter user name'."\r\n", false);
        $name = $helper->ask($input, $output, $question_name);

        if ($name){
            $this->io->success([$name]);
        }else{
            $this->askName($input, $output, $helper);
            //$this->io->warning(['abort']);
        }
    }

    private function askValue($question, $input, $output, $helper){
        $question_name = new Question($question."\r\n", false);
        $value = $helper->ask($input, $output, $question_name);

        if ($value){
            return $value;
            //$this->io->success([$name]);
        }else{
            $this->askValue($question, $input, $output, $helper);
            //$this->io->warning(['abort']);
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);


        if ($input->getOption('admin')){

            $helper = $this->getHelper('question');
            $output->writeln('You gonna registrate Admin user. To brake use ctrl+c');

            $name = $this->askValue('Enter user name', $input, $output, $helper);
            $pass = $this->askValue('Enter user pass', $input, $output, $helper);
            $phone = $this->askValue('Enter user phone (+79025554433 for example)', $input, $output, $helper);
            $email = $this->askValue('Enter user email', $input, $output, $helper);
            $roles = ["ROLE_SUPERUSER"];

                $user = new User();
                $user->setName($name)
                    ->setPhone($phone)
                    ->setEmail($email)
                    ->setDatein(new \DateTime())
                    ->setDateen(new \DateTime())
                    ->setRoles($roles)
                ;

                $hashedPassword = $this->passwordHasher->hashPassword($user,$pass);
                $user->setPassword($hashedPassword);

                $this->em->persist($user);
                $this->em->flush();

                $this->io->success(['You just create user: ',
                'Id: '.$user->getId(), 'Name: '.$user->getName(), 'Phone: '.$user->getPhone(), 'Email: '.$user->getEmail(), 'Roles: '.json_encode($user->getRoles())]
                );


        }else{
            $this->io->warning([
                'use --admin to create admin user',
                'Use -h for full help'
            ]);
        }
        return Command::SUCCESS;
    }
}