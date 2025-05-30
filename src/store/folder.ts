import type { ActionParams } from '../store/index.ts'
import type { Feed } from '../types/Feed.ts'
import type { Folder } from '../types/Folder.ts'

import _ from 'lodash'
import { reactive } from 'vue'
import { FolderService } from '../dataservices/folder.service'
import { FEED_MUTATION_TYPES, FOLDER_MUTATION_TYPES } from '../types/MutationTypes.ts'

export const FOLDER_ACTION_TYPES = {
	FETCH_FOLDERS: 'FETCH_FOLDERS',
	ADD_FOLDERS: 'ADD_FOLDER',
	DELETE_FOLDER: 'DELETE_FOLDER',

	FOLDER_SET_NAME: 'FOLDER_SET_NAME',
	FOLDER_OPEN_STATE: 'FOLDER_OPEN_STATE',
}

export type FolderState = {
	folders: Folder[]
}

const state: FolderState = reactive({
	folders: [],
})

const getters = {
	folders(state: FolderState) {
		return state.folders
	},
}

export const actions = {
	async [FOLDER_ACTION_TYPES.FETCH_FOLDERS]({ commit }: ActionParams<FolderState>) {
		const folders = await FolderService.fetchAllFolders()

		commit(FOLDER_MUTATION_TYPES.SET_FOLDERS, folders.data.folders)
	},
	async [FOLDER_ACTION_TYPES.ADD_FOLDERS](
		{ commit }: ActionParams<FolderState>,
		{ folder }: { folder: Folder },
	) {
		const response = await FolderService.createFolder({ name: folder.name })
		commit(FOLDER_MUTATION_TYPES.SET_FOLDERS, response.data.folders)
	},
	async [FOLDER_ACTION_TYPES.DELETE_FOLDER](
		{ commit }: ActionParams<FolderState>,
		{ folder }: { folder: Folder },
	) {
		await FolderService.deleteFolder({ id: folder.id })
		commit(FOLDER_MUTATION_TYPES.DELETE_FOLDER, folder)
	},
	async [FOLDER_ACTION_TYPES.FOLDER_SET_NAME](
		{ commit }: ActionParams<FolderState>,
		{ folder, name }: { folder: Folder, name: string },
	) {
		await FolderService.renameFolder({ id: folder.id, name })
		commit(FOLDER_MUTATION_TYPES.UPDATE_FOLDER, { id: folder.id, name })
	},
	async [FOLDER_ACTION_TYPES.FOLDER_OPEN_STATE](
		state: ActionParams<FolderState>,
		{ folder }: { folder: Folder },
	) {
		await FolderService.folderOpenState({ id: folder.id, opened: folder.opened })
	},
}

export const mutations = {
	[FOLDER_MUTATION_TYPES.SET_FOLDERS](
		state: FolderState,
		folders: Folder[],
	) {
		const updatedFolders = [...state.folders, ...folders]
			.filter((folder, index, self) => self.findIndex((f) => f.id === folder.id) === index)
			.sort((a, b) => a.name.localeCompare(b.name))

		state.folders = updatedFolders
	},

	[FOLDER_MUTATION_TYPES.DELETE_FOLDER](
		state: FolderState,
		folder: Folder,
	) {
		const index = state.folders.indexOf(folder)
		state.folders.splice(index, 1)
	},

	[FEED_MUTATION_TYPES.SET_FEEDS](
		state: FolderState,
		feeds: Feed[],
	) {
		const updatedFolders = state.folders.map((folder) => ({
			...folder,
			feeds: [] as Feed[],
			feedCount: 0,
			updateErrorCount: 0,
		}))
		feeds.forEach((it) => {
			const folder = updatedFolders.find((folder: Folder) => {
				return folder.id === it.folderId
			})
			if (folder) {
				folder.feeds.push(it)
				folder.feedCount += it.unreadCount
				if (it.updateErrorCount > 8) {
					folder.updateErrorCount += it.updateErrorCount
				}
			}
		})
		state.folders = updatedFolders
	},

	[FEED_MUTATION_TYPES.ADD_FEED](
		state: FolderState,
		feed: Feed,
	) {
		const folder = state.folders.find((folder: Folder) => {
			return folder.id === feed.folderId
		})
		if (folder) {
			folder.feeds.push(feed)
			folder.feedCount += feed.unreadCount
			folder.updateErrorCount += feed.updateErrorCount
		}
	},

	[FOLDER_MUTATION_TYPES.UPDATE_FOLDER](
		state: FolderState,
		newFolder: Folder,
	) {
		const folder = state.folders.find((f: Folder) => {
			return f.id === newFolder.id
		})
		if (folder) {
			_.assign(folder, newFolder)
		}
	},

	[FOLDER_MUTATION_TYPES.REMOVE_FOLDER_FEED](
		state: FolderState,
		{ feedId, folderId, unreadCount }: { feedId: number, folderId: number, unreadCount: number },
	) {
		const folder = state.folders.find((folder: Folder) => {
			return folder.id === folderId
		})
		if (folder) {
			folder.feedCount -= unreadCount
			const updatedFeeds = folder.feeds.filter((feed: Feed) => {
				return feed.id !== feedId
			})
			folder.feeds = [...updatedFeeds]
		}
	},

	[FOLDER_MUTATION_TYPES.MODIFY_FOLDER_UNREAD_COUNT](
		state: FolderState,
		{ folderId, delta }: { folderId: number, delta: number },
	) {
		const folder = state.folders.find((f: Folder) => {
			return f.id === folderId
		})
		if (folder) {
			folder.feedCount += delta
		}
	},

	[FEED_MUTATION_TYPES.SET_FEED_ALL_READ](
		state: FolderState,
		feed: Feed,
	) {
		const folder = state.folders.find((folder: Folder) => {
			return folder.id === feed.folderId
		})

		if (folder) {
			folder.feedCount -= feed.unreadCount
		}
	},
}

export default {
	state,
	getters,
	actions,
	mutations,
}
