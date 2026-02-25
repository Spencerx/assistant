<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Assistant\Migration;

use Closure;
use OCA\Assistant\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version020201Date20250120174409 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// some app values were types as INT
		// https://github.com/nextcloud/assistant/issues/174
		$keysToFix = [
			'assistant_enabled',
			'chat_last_n_messages',
			'free_prompt_picker_enabled',
			'speech_to_text_picker_enabled',
			'text_to_image_picker_enabled',
		];
		foreach ($keysToFix as $key) {
			if ($this->appConfig->hasKey(Application::APP_ID, $key)
				&& $this->appConfig->getValueType(Application::APP_ID, $key, lazy: true) === IAppConfig::VALUE_INT) {
				$value = $this->appConfig->getValueInt(Application::APP_ID, $key, lazy: true);
				$this->appConfig->deleteKey(Application::APP_ID, $key);
				if ($key == 'assistant_enabled') {
					// do not lazy store assistant_enabled as it is needed for capabilities
					$this->appConfig->setValueString(Application::APP_ID, $key, (string)$value);
				} else {
					$this->appConfig->setValueString(Application::APP_ID, $key, (string)$value, lazy: true);
				}
			}
		}
	}
}
