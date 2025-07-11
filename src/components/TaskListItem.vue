<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem
		class="task-list-item"
		:name="mainName"
		:title="subName"
		:bold="false"
		:active="active"
		:details="details"
		@click="$emit('load')">
		<template #icon>
			<component :is="icon"
				style="margin-right: 8px;"
				:title="statusTitle" />
		</template>
		<template v-if="onlyHasAudioInput" #name>
			<div class="item-audio-io">
				<MicrophoneMessageIcon class="item-mic-icon" />
				<span>{{ t('assistant', 'Audio input') }}</span>
			</div>
		</template>
		<template #subname>
			<div v-if="isSuccessful && isText2Image"
				class="inline-images">
				<ImageDisplay
					v-for="fileId in task.output.images"
					:key="fileId"
					:file-id="fileId"
					:task-id="task.id"
					:is-output="true"
					:border-radius="3" />
			</div>
			<div v-else-if="isSuccessful && onlyHasAudioOutput" class="item-audio-io">
				<MicrophoneMessageIcon />
				<span>{{ t('assistant', 'Audio output') }}</span>
			</div>
			<span v-else>
				{{ subName }}
			</span>
		</template>
		<!--template #indicator>
			<CheckboxBlankCircle :size="16" fill-color="#fff" />
		</template-->
		<template #actions>
			<NcActionButton @click="$emit('try-again')">
				<template #icon>
					<ReloadIcon />
				</template>
				{{ t('assistant', 'Try again') }}
			</NcActionButton>
			<NcActionButton v-if="isScheduled || isRunning"
				:close-after-click="true"
				@click="$emit('cancel')">
				<template #icon>
					<CloseIcon />
				</template>
				{{ t('assistant', 'Cancel') }}
			</NcActionButton>
			<NcActionButton @click="$emit('delete')">
				<template #icon>
					<TrashCanOutlineIcon />
				</template>
				{{ t('assistant', 'Delete') }}
			</NcActionButton>
		</template>
	</NcListItem>
</template>

<script>
import CancelIcon from 'vue-material-design-icons/Cancel.vue'
import ReloadIcon from 'vue-material-design-icons/Reload.vue'
import ProgressQuestionIcon from 'vue-material-design-icons/ProgressQuestion.vue'
import ProgressCheckIcon from 'vue-material-design-icons/ProgressCheck.vue'
import ProgressClockIcon from 'vue-material-design-icons/ProgressClock.vue'
import AlertCircleOutlineIcon from 'vue-material-design-icons/AlertCircleOutline.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import TrashCanOutlineIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import MicrophoneMessageIcon from 'vue-material-design-icons/MicrophoneMessage.vue'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import moment from '@nextcloud/moment'

import { TASK_STATUS_STRING, SHAPE_TYPE_NAMES } from '../constants.js'
import ImageDisplay from './fields/ImageDisplay.vue'

const statusIcons = {
	[TASK_STATUS_STRING.successful]: CheckIcon,
	[TASK_STATUS_STRING.cancelled]: CancelIcon,
	[TASK_STATUS_STRING.failed]: AlertCircleOutlineIcon,
	[TASK_STATUS_STRING.running]: ProgressCheckIcon,
	[TASK_STATUS_STRING.scheduled]: ProgressClockIcon,
}

const statusTitles = {
	[TASK_STATUS_STRING.successful]: t('assistant', 'Succeeded'),
	[TASK_STATUS_STRING.cancelled]: t('assistant', 'Cancelled'),
	[TASK_STATUS_STRING.failed]: t('assistant', 'Failed'),
	[TASK_STATUS_STRING.running]: t('assistant', 'Running'),
	[TASK_STATUS_STRING.scheduled]: t('assistant', 'Scheduled'),
}

export default {
	name: 'TaskListItem',

	components: {
		ImageDisplay,
		NcListItem,
		NcActionButton,
		CloseIcon,
		TrashCanOutlineIcon,
		ProgressClockIcon,
		ProgressCheckIcon,
		ProgressQuestionIcon,
		CheckIcon,
		AlertCircleOutlineIcon,
		ReloadIcon,
		MicrophoneMessageIcon,
	},

	props: {
		active: {
			type: Boolean,
			default: false,
		},
		task: {
			type: Object,
			required: true,
		},
		taskType: {
			type: [Object, null],
			default: null,
		},
	},

	emits: [
		'delete',
		'cancel',
		'try-again',
		'load',
	],

	data() {
		return {
			copied: false,
		}
	},

	computed: {
		isRunning() {
			return this.task.status === TASK_STATUS_STRING.running
		},
		isScheduled() {
			return this.task.status === TASK_STATUS_STRING.scheduled
		},
		isSuccessful() {
			return this.task.status === TASK_STATUS_STRING.successful
		},
		isText2Image() {
			return this.task.type === 'core:text2image'
		},
		onlyHasAudioInput() {
			return Object.values(this.taskType.inputShape)
				.every(field => field.type === SHAPE_TYPE_NAMES.Audio)
		},
		onlyHasAudioOutput() {
			return Object.values(this.taskType.outputShape)
				.every(field => field.type === SHAPE_TYPE_NAMES.Audio)
		},
		mainName() {
			return this.textInputPreview
		},
		subName() {
			if (this.task.status === TASK_STATUS_STRING.successful) {
				if (this.isText2Image) {
					const nbGeneratedImages = this.task.output?.length ?? 0
					return n('assistant', '{n} image generated', '{n} images generated', nbGeneratedImages, { n: nbGeneratedImages })
				}
				return this.textOutputPreview
			} else if (this.task.status === TASK_STATUS_STRING.scheduled) {
				if (this.isText2Image) {
					const nbImageAsked = this.task.input.numberOfImages
					return n('assistant', '{n} image scheduled', '{n} images scheduled', nbImageAsked, { n: nbImageAsked })
				}
				return t('assistant', 'Task scheduled')
			}
			return statusTitles[this.task.status] ?? t('assistant', 'Unknown status')
		},
		details() {
			return moment.unix(this.task.lastUpdated).fromNow()
		},
		icon() {
			return statusIcons[this.task.status] ?? ProgressQuestionIcon
		},
		statusTitle() {
			return statusTitles[this.task.status] ?? t('assistant', 'Unknown status')
		},
		textInputPreview() {
			const textInputs = []
			Object.keys(this.taskType.inputShape).forEach(key => {
				const field = this.taskType.inputShape[key]
				if (field.type === SHAPE_TYPE_NAMES.Text) {
					textInputs.push(this.task.input[key])
				}
			})
			return textInputs.join(' | ')
		},
		textOutputPreview() {
			if (!this.isSuccessful) {
				return null
			}
			const textOutputs = []
			Object.keys(this.taskType.outputShape).forEach(key => {
				const field = this.taskType.outputShape[key]
				if (field.type === SHAPE_TYPE_NAMES.Text) {
					textOutputs.push(this.task.output[key])
				}
			})
			return textOutputs.join(' | ')
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
	},
}
</script>

<style lang="scss">
:deep(.task-list-item) {
	.list-item {
		width: 99% !important;
		// TODO fix in NcListItem
		&-content__name {
			max-width: unset !important;
		}
	}
}

.inline-images {
	display: flex;
	gap: 4px;
	img {
		height: 28px;
		width: 28px;
	}
}

.item-audio-io {
	display: flex;
	align-items: center;
	gap: 8px;
}
</style>
