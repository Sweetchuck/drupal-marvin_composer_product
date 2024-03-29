<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_composer_product\Integration;

/**
 * @group marvin_product
 * @group marvin_composer_product
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin_composer_product\ComposerCommands
 */
class MarvinLintComposerValidateTest extends UnishIntegrationTestCase {

  public function testLintComposerValidate(): void {
    $expected = [
      'stdError' => implode(PHP_EOL, [
        ' [Composer\Validate] Running composer validate in ' . $this->getMarvinProductRootDir(),
      ]),
      'stdOutput' => './composer.json is valid',
      'exitCode' => 0,
    ];

    $this->drush(
      'marvin:lint:composer-validate',
      [],
      $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $this->getCommonCommandLineEnvVars()
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertStringContainsString($expected['stdError'], $actualStdError, 'StdError');
    static::assertStringContainsString($expected['stdOutput'], $actualStdOutput, 'StdOutput');
  }

}
