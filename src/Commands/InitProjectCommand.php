<?php

namespace PhpMvc\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InitProjectCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialise un nouveau projet MVC')
            ->setHelp('Cette commande vous guide pour créer un projet MVC complet');
    }

    /**
     * Exécution de la commande
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Affichage du header
        $this->displayHeader($output);

        // On récupère le QuestionHelper pour poser des questions
        $helper = $this->getHelper('question');

        // 1. Nom du projet
        $projectName = $this->askProjectName($input, $output, $helper);

        // 2. Nombre d'entités
        $entityCount = $this->askEntityCount($input, $output, $helper);

        // 3. Détails de chaque entité
        $entities = $this->askEntitiesDetails($input, $output, $helper, $entityCount);

        // 4. Bootstrap oui/non
        $withBootstrap = $this->askBootstrap($input, $output, $helper);

        // 5. Authentification oui/non
        $withAuth = $this->askAuth($input, $output, $helper);

        // Affichage du récapitulatif
        $this->displaySummary($output, $projectName, $entities, $withBootstrap, $withAuth);

        // Message de succès
        $this->displaySuccess($output, $projectName);

        // Retourne 0 = succès (1 = erreur)
        return Command::SUCCESS;
    }

    /**
     * Affiche le header de bienvenue
     */
    private function displayHeader(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(' <fg=cyan>╔════════════════════════════════════════╗</>');
        $output->writeln(' <fg=cyan>║</> <fg=white;options=bold>  🚀 Générateur MVC PhpMvc            </><fg=cyan>║</>');
        $output->writeln(' <fg=cyan>╚════════════════════════════════════════╝</>');
        $output->writeln('');
    }

    /**
     * Demande le nom du projet
     */
    private function askProjectName(InputInterface $input, OutputInterface $output, $helper): string
    {
        $question = new Question(' <fg=yellow>📁 Nom du projet :</> ', 'mon-projet');
        $projectName = $helper->ask($input, $output, $question);

        return $projectName;
    }

    /**
     * Demande le nombre d'entités
     */
    private function askEntityCount(InputInterface $input, OutputInterface $output, $helper): int
    {
        $question = new Question(' <fg=yellow>🔢 Nombre d\'entités :</> ', 1);
        $question->setValidator(function ($answer) {
            if (!is_numeric($answer) || $answer < 1) {
                throw new \RuntimeException('Le nombre d\'entités doit être au moins 1');
            }
            return (int) $answer;
        });

        return $helper->ask($input, $output, $question);
    }

    /**
     * Demande les détails de chaque entité
     */
    private function askEntitiesDetails(InputInterface $input, OutputInterface $output, $helper, int $count): array
    {
        $entities = [];

        for ($i = 1; $i <= $count; $i++) {
            $output->writeln('');
            $output->writeln(" <fg=green>━━━ Entité #{$i} ━━━</>");

            // Nom de l'entité
            $nameQuestion = new Question(" <fg=yellow>📝 Nom de l'entité #{$i} :</> ");
            $entityName = $helper->ask($input, $output, $nameQuestion);

            // Champs de l'entité
            $fields = $this->askEntityFields($input, $output, $helper, $entityName);

            $entities[] = [
                'name' => $entityName,
                'fields' => $fields
            ];
        }

        return $entities;
    }

    /**
     * Demande les champs d'une entité
     */
    private function askEntityFields(InputInterface $input, OutputInterface $output, $helper, string $entityName): array
    {
        $fields = [];
        $output->writeln(" <fg=cyan>   Champs de l'entité {$entityName} (laissez vide pour terminer)</>");

        while (true) {
            // Nom du champ
            $fieldQuestion = new Question('   <fg=yellow>➜ Nom du champ :</> ');
            $fieldName = $helper->ask($input, $output, $fieldQuestion);

            // Si vide, on arrête
            if (empty($fieldName)) {
                break;
            }

            // Type du champ
            $typeQuestion = new Question('   <fg=yellow>➜ Type (string/int/float/bool/datetime) :</> ', 'string');
            $fieldType = $helper->ask($input, $output, $typeQuestion);

            $fields[] = [
                'name' => $fieldName,
                'type' => $fieldType
            ];

            $output->writeln("   <fg=green>✓</> Champ '{$fieldName}' ({$fieldType}) ajouté");
        }

        return $fields;
    }

    /**
     * Demande si on veut Bootstrap
     */
    private function askBootstrap(InputInterface $input, OutputInterface $output, $helper): bool
    {
        $output->writeln('');
        $question = new ConfirmationQuestion(' <fg=yellow>🎨 Générer les vues Bootstrap ? (y/n) :</> ', false);
        return $helper->ask($input, $output, $question);
    }

    /**
     * Demande si on veut l'authentification
     */
    private function askAuth(InputInterface $input, OutputInterface $output, $helper): bool
    {
        $output->writeln('');
        $question = new ConfirmationQuestion(' <fg=yellow>🔐 Générer le système d\'authentification ? (y/n) :</> ', false);
        return $helper->ask($input, $output, $question);
    }

    /**
     * Affiche le récapitulatif
     */
    private function displaySummary(OutputInterface $output, string $projectName, array $entities, bool $withBootstrap, bool $withAuth): void
    {
        $output->writeln('');
        $output->writeln(' <fg=cyan>═══════════════════════════════════════</>');
        $output->writeln(' <fg=white;options=bold>📋 Récapitulatif</>');
        $output->writeln(' <fg=cyan>═══════════════════════════════════════</>');
        $output->writeln('');
        $output->writeln(" <fg=green>Projet :</> {$projectName}");
        $output->writeln(" <fg=green>Entités :</> " . count($entities));

        foreach ($entities as $entity) {
            $output->writeln("   • {$entity['name']} (" . count($entity['fields']) . " champs)");
        }

        $output->writeln(" <fg=green>Bootstrap :</> " . ($withBootstrap ? 'Oui ✓' : 'Non ✗'));
        $output->writeln(" <fg=green>Authentification :</> " . ($withAuth ? 'Oui ✓' : 'Non ✗'));
        $output->writeln('');
    }

    /**
     * Affiche le message de succès final
     */
    private function displaySuccess(OutputInterface $output, string $projectName): void
    {
        $output->writeln(' <fg=green;options=bold>✅ Projet généré avec succès !</>');
        $output->writeln('');
        $output->writeln(' <fg=red>╔═══════════════════════════════════════════╗</>');
        $output->writeln(' <fg=red>║  ⚠️  IMPORTANT - BASE DE DONNÉES         ║</>');
        $output->writeln(' <fg=red>╚═══════════════════════════════════════════╝</>');
        $output->writeln('');
        $output->writeln(' <fg=yellow>Avant de lancer votre projet :</>');
        $output->writeln('');
        $output->writeln(' <fg=cyan>1️⃣  Créez votre base de données MySQL</>');
        $output->writeln(' <fg=cyan>2️⃣  Configurez config/database.php</>');
        $output->writeln(' <fg=cyan>3️⃣  Lancez : php -S localhost:8000 -t public</>');
        $output->writeln('');
        $output->writeln(" <fg=green>📁 Votre projet :</> ./{$projectName}");
        $output->writeln('');
    }
}