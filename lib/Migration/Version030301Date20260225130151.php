<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Assistant\Migration;

use Closure;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version030301Date20260225130151 extends SimpleMigrationStep {
	private static array $configKeys = [
		'chat_user_instructions',
		'chat_user_instructions_title',
		'chat_last_n_messages',
		'free_prompt_picker_enabled',
		'text_to_image_picker_enabled',
		'text_to_sticker_picker_enabled',
		'speech_to_text_picker_enabled',
		'new_image_file_menu_plugin',
	]; // do not lazy store assistant_enabled as it is needed for capabilities
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$allSetKeys = $this->appConfig->getAppKeys();

		foreach (self::$configKeys as $key) {
			// skip if not already set
			if (!in_array($key, $allSetKeys)) {
				continue;
			}
			$value = $this->appConfig->getAppValueString($key);
			$this->appConfig->setAppValueString($key, $value, lazy: true);
		}
	}
}
