<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_composer_product;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin_composer\ComposerCommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;

class ComposerCommands extends ComposerCommandsBase {

  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:git-hook:pre-commit',
  )]
  public function onEventMarvinGitHookPreCommit(): array {
    return [
      'marvin_composer_product:composer-validate' => [
        'weight' => -201,
        'task' => $this->getTaskComposerValidate($this->getProjectRootDir()),
      ],
    ];
  }

  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:git-hook:post-checkout',
  )]
  public function onEventMarvinGitHookPostCheckout(): array {
    return [
      'marvin_composer_product:composer-changed-notification' => [
        'weight' => 100,
        'task' => $this->getTaskComposerChangedNotification(),
      ],
    ];
  }

  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:lint',
  )]
  public function onEventMarvinLint(): array {
    return [
      'marvin_composer_product:composer-validate' => [
        'weight' => -201,
        'task' => $this->getTaskComposerValidate($this->getProjectRootDir()),
      ],
    ];
  }

  /**
   * Runs `composer validate`.
   */
  #[CLI\Command(name: 'marvin:lint:composer-validate')]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  public function cmdMarvinLintComposerValidateExecute(): CollectionBuilder {
    return $this->getTaskComposerValidate($this->getProjectRootDir());
  }

  /**
   * @todo Check that if $path is empty.
   */
  protected function getTaskComposerStatus(string $refPrevious, string $refHead, array $paths): \Closure {
    return function () use ($refPrevious, $refHead, $paths): int {
      $cmdPattern = '%s diff --exit-code --name-only %s..%s --';
      $cmdArgs = [
        escapeshellcmd($this->getGitExecutable()),
        escapeshellarg($refPrevious),
        escapeshellarg($refHead),
      ];

      $cmdPattern .= str_repeat(' %s', count($paths));
      foreach ($paths as $path) {
        $cmdArgs[] = escapeshellarg($path);
      }

      $cmd = vsprintf($cmdPattern, $cmdArgs);

      $result = $this
        ->taskExec($cmd)
        ->run();

      if (!$result->wasSuccessful()) {
        $this->say('composer.{json,lock} has been changed changed. Run `composer install`');
      }

      return 0;
    };
  }

  /**
   * @todo Write native Task.
   *
   * @see \Drush\Commands\marvin_product\NpmCommands::getTaskPackageJsonNotification
   */
  protected function getTaskComposerChangedNotification(): \Closure {
    return function (RoboStateData $data): int {
      $fileNames = new \RegexIterator(
        new \ArrayIterator($data['changed.fileNames']),
        '@(^|/)composer\.(json|lock)$@'
      );

      $commands = [];
      foreach ($fileNames as $fileName) {
        $dirName = dirname($fileName) ?: '.';
        $commands[$dirName] = sprintf('cd %s && composer install', escapeshellarg($dirName));
      }

      if (!$commands) {
        return 0;
      }

      $message = implode(PHP_EOL, [
        'One of the composer.{json,lock} has been changed.',
        'You have to run the following commands:',
        '{commands}',
      ]);
      $context = [
        'commands' => implode(PHP_EOL, $commands),
      ];

      $this->getLogger()->warning($message, $context);

      return 0;
    };
  }

}
