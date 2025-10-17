<?php

namespace PhpMvc\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PhpMvc\Generators\StructureGenerator;
use PhpMvc\Generators\EntityGenerator;
use PhpMvc\Generators\ControllerGenerator;
use PhpMvc\Generators\RepositoryGenerator;
use PhpMvc\Generators\RoutesGenerator;
use PhpMvc\Generators\LayoutGenerator;
use PhpMvc\Generators\ViewGenerator;
use PhpMvc\Generators\AuthGenerator;

class InitProjectCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new MVC project')
            ->setHelp('This command guides you to create a complete MVC project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->displayHeader($output);

        $helper = $this->getHelper('question');

        // 1. Project name
        $projectName = $this->askProjectName($input, $output, $helper);

        // 2. Number of entities
        $entityCount = $this->askEntityCount($input, $output, $helper);

        // 3. Entity details
        $entities = $this->askEntitiesDetails($input, $output, $helper, $entityCount);

        // 4. Bootstrap yes/no
        $withBootstrap = $this->askBootstrap($input, $output, $helper);

        // 5. Authentication yes/no
        $withAuth = $this->askAuth($input, $output, $helper);

        // Display summary
        $this->displaySummary($output, $projectName, $entities, $withBootstrap, $withAuth);

        // Project generation
        $output->writeln('');
        $output->writeln(' <fg=cyan>⏳ Generating project...</>');
        $output->writeln('');

        try {
            // Structure generation
            $structureGenerator = new StructureGenerator($projectName);
            $structureGenerator->generate();

            $output->writeln(' <fg=green>✓ Project structure created</>');

            // Generate entities, repositories and controllers
            $projectPath = $structureGenerator->getProjectPath();

            foreach ($entities as $entity) {
                // Entity generation
                $entityGenerator = new EntityGenerator(
                    $projectPath,
                    $entity['name'],
                    $entity['fields']
                );
                $entityGenerator->generate();

                $output->writeln(" <fg=green>✓ Entity {$entity['name']} created</>");

                // Repository generation
                $repositoryGenerator = new RepositoryGenerator(
                    $projectPath,
                    $entity['name'],
                    $entity['fields']
                );
                $repositoryGenerator->generate();

                $output->writeln(" <fg=green>✓ Repository {$entity['name']}Repository created</>");

                // Controller generation (with Bootstrap parameter)
                $controllerGenerator = new ControllerGenerator(
                    $projectPath,
                    $entity['name'],
                    $entity['fields'],
                    $withBootstrap
                );
                $controllerGenerator->generate();

                $output->writeln(" <fg=green>✓ Controller {$entity['name']}Controller created</>");
            }

            // Generate Bootstrap views if enabled
            if ($withBootstrap) {
                // Generate layout
                $layoutGenerator = new LayoutGenerator($projectPath, $projectName);
                $layoutGenerator->generate();

                $output->writeln(' <fg=green>✓ Layout created</>');

                // Generate views for each entity
                foreach ($entities as $entity) {
                    $viewGenerator = new ViewGenerator(
                        $projectPath,
                        $entity['name'],
                        $entity['fields']
                    );
                    $viewGenerator->generate();

                    $output->writeln(" <fg=green>✓ View {$entity['name']} created</>");
                }
            }

            if ($withAuth) {
                $authGenerator = new AuthGenerator($projectPath, $withBootstrap);
                $authGenerator->generate();

                $output->writeln(' <fg=green>✓ User entity created</>');
                $output->writeln(' <fg=green>✓ UserRepository created</>');
                $output->writeln(' <fg=green>✓ AuthController created</>');
                $output->writeln(' <fg=green>✓ Session management created</>');
                $output->writeln(' <fg=green>✓ Auth middleware created</>');

                if ($withBootstrap) {
                    $output->writeln(' <fg=green>✓ Login view created</>');
                    $output->writeln(' <fg=green>✓ Register view created</>');
                }
            }

            // Generate routes
            $routesGenerator = new RoutesGenerator($projectPath, $entities);
            $routesGenerator->generate();

            $output->writeln(' <fg=green>✓ Routes configured</>');

        } catch (\Exception $e) {
            $output->writeln(' <fg=red>✗ Error: ' . $e->getMessage() . '</>');
            return Command::FAILURE;
        }

        // Success message
        $this->displaySuccess($output, $projectName);

        return Command::SUCCESS;
    }

    private function displayHeader(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(' <fg=cyan>╔════════════════════════════════════════╗</>');
        $output->writeln(' <fg=cyan>║</> <fg=white;options=bold>  🚀 PhpMvc Generator                 </><fg=cyan>║</>');
        $output->writeln(' <fg=cyan>╚════════════════════════════════════════╝</>');
        $output->writeln('');
    }

    private function askProjectName(InputInterface $input, OutputInterface $output, $helper): string
    {
        $question = new Question(' <fg=yellow>📁 Project name:</> ', 'my-project');
        $projectName = $helper->ask($input, $output, $question);

        return $projectName;
    }

    private function askEntityCount(InputInterface $input, OutputInterface $output, $helper): int
    {
        $question = new Question(' <fg=yellow>🔢 Number of entities:</> ', 1);
        $question->setValidator(function ($answer) {
            if (!is_numeric($answer) || $answer < 1) {
                throw new \RuntimeException('The number of entities must be at least 1');
            }
            return (int) $answer;
        });

        return $helper->ask($input, $output, $question);
    }

    private function askEntitiesDetails(InputInterface $input, OutputInterface $output, $helper, int $count): array
    {
        $entities = [];

        for ($i = 1; $i <= $count; $i++) {
            $output->writeln('');
            $output->writeln(" <fg=green>━━━ Entity #{$i} ━━━</>");

            $nameQuestion = new Question(" <fg=yellow>📝 Entity #{$i} name:</> ");
            $entityName = $helper->ask($input, $output, $nameQuestion);

            $fields = $this->askEntityFields($input, $output, $helper, $entityName);

            $entities[] = [
                'name' => $entityName,
                'fields' => $fields
            ];
        }

        return $entities;
    }

    private function askEntityFields(InputInterface $input, OutputInterface $output, $helper, string $entityName): array
    {
        $fields = [];
        $output->writeln(" <fg=cyan>   {$entityName} fields (leave empty to finish)</>");

        while (true) {
            $fieldQuestion = new Question('   <fg=yellow>➜ Field name:</> ');
            $fieldName = $helper->ask($input, $output, $fieldQuestion);

            if (empty($fieldName)) {
                break;
            }

            $typeQuestion = new Question('   <fg=yellow>➜ Type (string/int/float/bool/datetime):</> ', 'string');
            $fieldType = $helper->ask($input, $output, $typeQuestion);

            $fields[] = [
                'name' => $fieldName,
                'type' => $fieldType
            ];

            $output->writeln("   <fg=green>✓</> Field '{$fieldName}' ({$fieldType}) added");
        }

        return $fields;
    }

    private function askBootstrap(InputInterface $input, OutputInterface $output, $helper): bool
    {
        $output->writeln('');
        $question = new ConfirmationQuestion(' <fg=yellow>🎨 Generate Bootstrap views? (y/n):</> ', false);
        return $helper->ask($input, $output, $question);
    }

    private function askAuth(InputInterface $input, OutputInterface $output, $helper): bool
    {
        $output->writeln('');
        $question = new ConfirmationQuestion(' <fg=yellow>🔐 Generate authentication system? (y/n):</> ', false);
        return $helper->ask($input, $output, $question);
    }

    private function displaySummary(OutputInterface $output, string $projectName, array $entities, bool $withBootstrap, bool $withAuth): void
    {
        $output->writeln('');
        $output->writeln(' <fg=cyan>═══════════════════════════════════════</>');
        $output->writeln(' <fg=white;options=bold>📋 Summary</>');
        $output->writeln(' <fg=cyan>═══════════════════════════════════════</>');
        $output->writeln('');
        $output->writeln(" <fg=green>Project:</> {$projectName}");
        $output->writeln(" <fg=green>Entities:</> " . count($entities));

        foreach ($entities as $entity) {
            $output->writeln("   • {$entity['name']} (" . count($entity['fields']) . " fields)");
        }

        $output->writeln(" <fg=green>Bootstrap:</> " . ($withBootstrap ? 'Yes ✓' : 'No ✗'));
        $output->writeln(" <fg=green>Authentication:</> " . ($withAuth ? 'Yes ✓' : 'No ✗'));
        $output->writeln('');
    }

    private function displaySuccess(OutputInterface $output, string $projectName): void
    {
        $output->writeln(' <fg=green;options=bold>✅ Project generated successfully!</>');
        $output->writeln('');
        $output->writeln(' <fg=red>╔═══════════════════════════════════════════╗</>');
        $output->writeln(' <fg=red>║  ⚠️  IMPORTANT - DATABASE                ║</>');
        $output->writeln(' <fg=red>╚═══════════════════════════════════════════╝</>');
        $output->writeln('');
        $output->writeln(' <fg=yellow>Before running your project:</>');
        $output->writeln('');
        $output->writeln(' <fg=cyan>1️⃣  Create your MySQL database</>');
        $output->writeln(' <fg=cyan>2️⃣  Configure config/database.php</>');
        $output->writeln(' <fg=cyan>3️⃣  Run: php -S localhost:8000 -t public</>');
        $output->writeln('');
        $output->writeln(" <fg=green>📁 Your project:</> ./{$projectName}");
        $output->writeln('');
    }
}