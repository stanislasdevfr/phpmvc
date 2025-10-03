<?php

namespace PhpMvc\Console;

use Symfony\Component\Console\Application as BaseApplication;
use PhpMvc\Commands\InitProjectCommand;

class Application extends BaseApplication
{
    /**
     * Le nom de notre application
     */
    private const NAME = 'PhpMvc Generator';
    
    /**
     * La version de notre application
     */
    private const VERSION = '1.0.0';

    public function __construct()
    {
        // On appelle le constructeur parent avec le nom et la version
        parent::__construct(self::NAME, self::VERSION);

        // On enregistre nos commandes
        $this->registerCommands();
    }

    /**
     * Enregistre toutes les commandes disponibles
     */
    private function registerCommands(): void
    {
        // Pour l'instant, on a juste la commande "init"
        // Plus tard, on pourra ajouter d'autres commandes ici
        $this->add(new InitProjectCommand());
    }
}