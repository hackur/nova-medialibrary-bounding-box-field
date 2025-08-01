<template>
  <Modal :show="show" @close-via-escape="$emit('close')" role="dialog" maxWidth="2xl">
    <div class="overflow-hidden rounded-lg bg-white shadow-lg dark:bg-gray-800">
      <ModalHeader class="flex items-center">
        {{ __('Choose existing media') }}
      </ModalHeader>

      <ModalContent>
        <IndexSearchInput
            class="mb-2"
            :searchable="true"
            v-model="search"
        />

        <LoadingView :loading="loading">
          <slot>
            <div class="mb-6">
              <ChooseExistingMediaList
                :media="media"
                :chosen-media="chosenMedia"
                @choose="chooseMedia"
                @unchoose="unchooseMedia"
              />
            </div>
          </slot>
        </LoadingView>
        <PaginationSimple v-bind="pagination" @page="selectPage"
          ><span class="text-xs"
            >{{ (pagination.from || 0) }}-{{ (pagination.to || 0) }} of {{ pagination.allMatchingResourceCount }}</span
          ></PaginationSimple
        >
      </ModalContent>
      <ModalFooter>
        <div class="ml-auto">
          <Button variant="link" state="mellow" class="ml-auto mr-3" @click="$emit('close')">
            {{ __('Cancel') }}
          </Button>
          <loading-button type="button" :disabled="loading" :loading="loading" ref="confirmButton" @click="handleChoose">
            {{ __('Choose') }}
            <span v-if="chosenMedia.length > 0"> ({{ chosenMedia.length }}) </span>
          </loading-button>
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import debounce from 'lodash/debounce'
import { Button } from 'laravel-nova-ui'
import PaginationButton from '../PaginationButton'
import ChooseExistingMediaList from '../ChooseExistingMediaList'

export default {
  emits: ['choose', 'close'],

  components: {
    Button,
    PaginationButton,
    ChooseExistingMediaList,
  },

  props: {
    show: {
      type: Boolean,
      default: false,
    },
    resourceName: {
      type: String,
      required: true,
    },
    resourceId: {
      required: true,
    },
    field: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      loading: false,
      media: [],
      chosenMedia: [],
      search: '',
      params: {
        name: '',
        mimeType: this.field.accept || '',
        maxSize: this.field.maxSize || '',
      },
      pagination: {
        allMatchingResourceCount: 0,
        resourceCountLabel: this.__('Media'),
        page: 0,
        pages: 0,
        from: 0,
        to: 0,
        next: false,
        previous: false,
      },
      resourceResponseError: null,
    }
  },

  created() {
    const debouncer = debounce(callback => callback(), 500)

    this.$watch('search', newValue => {
      this.search = newValue
      debouncer(() => this.applyFilter(newValue))
    })

    this.getResources(this.params)
  },

  methods: {
    getResources(params) {
      const { attribute } = this.field
      const { resourceName, resourceId } = this

      this.loading = true

      return Nova.request()
        .get(
          `/nova-vendor/dmitrybubyakin/nova-medialibrary-field/${resourceName}/${resourceId}/media/${attribute}/attachable`,
          { params }
        )
        .then(({ data }) => {
          this.media = data.data

          this.pagination.allMatchingResourceCount = data.total
          this.pagination.page = data.current_page
          this.pagination.pages = data.last_page
          this.pagination.from = data.from
          this.pagination.to = data.to
          this.pagination.previous = data.prev_page_url ? true : false
          this.pagination.next = data.next_page_url ? true : false

          Nova.$emit('resources-loaded', {
            resourceName: this.resourceName,
            mode: 'index',
          })

          this.loading = false
        })
        .catch((e) => {
          this.loading = false
          this.resourceResponseError = e

          throw e
        })
    },

    applyFilter(keyword) {
      this.params.name = keyword
      this.selectPage(1)
    },

    selectPage(page) {
      if (!page) {
        return
      }

      this.getResources({ ...this.params, page })
    },

    chooseMedia(media) {
      if (this.field.single) {
        this.chosenMedia = []
      }

      this.chosenMedia.push(media)
    },

    unchooseMedia(media) {
      this.chosenMedia = this.chosenMedia.filter((m) => m.id !== media.id)
    },

    handleChoose() {
      this.$emit('choose', this.chosenMedia)
    },
  },
}
</script>
