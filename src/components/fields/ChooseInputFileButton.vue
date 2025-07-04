<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcButton
		v-bind="$attrs"
		variant="secondary"
		@click="onButtonClick">
		<template #icon>
			<FolderPlusOutlineIcon />
		</template>
		{{ label }}
	</NcButton>
</template>

<script>
import FolderPlusOutlineIcon from 'vue-material-design-icons/FolderPlusOutline.vue'

import NcButton from '@nextcloud/vue/components/NcButton'

import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'

export default {
	name: 'ChooseInputFileButton',

	components: {
		NcButton,
		FolderPlusOutlineIcon,
	},

	props: {
		label: {
			type: String,
			default: t('assistant', 'Choose file'),
		},
		pickerTitle: {
			type: String,
			default: t('assistant', 'Choose a file'),
		},
		accept: {
			type: Array,
			default: () => [],
		},
		multiple: {
			type: Boolean,
			default: false,
		},
	},

	emits: [
		'files-chosen',
	],

	data() {
		return {
			picker: (callback) => getFilePickerBuilder(this.pickerTitle)
				.setMimeTypeFilter(this.accept)
				.setMultiSelect(this.multiple)
				.allowDirectories(false)
				.addButton({
					id: 'choose-input-file',
					label: t('assistant', 'Choose'),
					variant: 'primary',
					callback: callback(),
				})
				.build(),
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		async onButtonClick() {
			await this.picker(this.pickerSubmitted).pick()
		},
		pickerSubmitted() {
			return (nodes) => {
				if (!nodes || nodes.length === 0 || !nodes[0].path) {
					showError(t('assistant', 'No file selected'))
					return
				}
				console.debug('[assistant] nodes', nodes)
				this.$emit('files-chosen', this.multiple ? nodes : nodes[0])
			}
		},
	},
}
</script>

<style lang="scss">
// nothing yet
</style>
