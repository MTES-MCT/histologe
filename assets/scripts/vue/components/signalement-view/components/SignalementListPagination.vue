<template>
  <div class="fr-grid-row fr-mb-1w fr-grid-row--center">
    <nav role="navigation" class="fr-pagination" aria-label="Pagination">
      <ul class="fr-pagination__list">
        <li>
          <a :href="pagination.current_page > 1 ? '?page=1' : null"
             @click.prevent="pagination.current_page > 1 && $emit('changePage', 1)"
             :aria-disabled="pagination.current_page <= 1"
             class="fr-pagination__link fr-pagination__link--first"
             :role="pagination.current_page <= 1 ? 'link' : null">Première page </a>
        </li>
        <li>
          <a :href="pagination.current_page > 1 ? '?page=' +  (pagination.current_page - 1) : null"
             @click.prevent="pagination.current_page > 1 && $emit('changePage', pagination.current_page - 1)"
             class="fr-pagination__link fr-pagination__link--prev fr-pagination__link--lg-label"
             :aria-disabled="pagination.current_page <= 1"
             :role="pagination.current_page <= 1 ? 'link' : null"> Page précédente
          </a>
        </li>
        <li v-for="page in pageNumbers" :key="page">
          <a :href="page !== '…' ? `?page=${page}` : null"
             @click.prevent="page !== '…' && $emit('changePage', page)"
             class="fr-pagination__link"
             :class="{ 'fr-pagination__link--active': typeof page === 'number' && page === pagination.current_page }"
             :title="`Page ${page}`"
             :aria-current="typeof page === 'number' && page === pagination.current_page ? 'page' : null">{{ page }}</a>
        </li>
        <li>
          <a :href="pagination.current_page < pagination.total_pages ? '?page=' +  (pagination.current_page + 1) : null"
             @click.prevent="pagination.current_page < pagination.total_pages && $emit('changePage', pagination.current_page + 1)"
             :aria-disabled="pagination.current_page < pagination.total_pages"
             class="fr-pagination__link fr-pagination__link--next fr-pagination__link--lg-label"
             :role="pagination.current_page === pagination.total_pages ? 'link' : null">
            Page suivante </a>
        </li>

        <li>
          <a :href="pagination.current_page !== pagination.total_pages ? `?page=${pagination.total_pages}` : null"
             @click.prevent="pagination.current_page < pagination.total_pages && $emit('changePage', pagination.total_pages)"
             :aria-disabled="pagination.current_page === pagination.total_pages"
             class="fr-pagination__link fr-pagination__link--last"
             :role="pagination.current_page === pagination.total_pages ? 'link' : null">
            Dernière page </a>
        </li>
      </ul>
    </nav>
  </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'SignalementListPagination',
  props: {
    pagination: {
      type: Object as () => {current_page: number, total_pages: number},
      required: true
    }
  },
  emits: ['changePage'],
  computed: {
    pageNumbers () {
      let pages = []
      const currentPage = this.pagination.current_page
      const totalPage = this.pagination.total_pages
      const limitPageToShow = 5
      const offsetPage = totalPage - limitPageToShow
      const addPages = (start : number, end : number) => {
        for (let i = start; i <= end; i++) {
          pages.push(i)
        }
      }

      if (currentPage < limitPageToShow) {
        if (totalPage <= limitPageToShow) {
          addPages(1, totalPage)
        } else if (totalPage > limitPageToShow) {
          addPages(1, limitPageToShow)
          if (offsetPage > 1) {
            pages.push('…')
          }
          pages.push(this.pagination.total_pages.toString())
        }
      } else if (currentPage >= limitPageToShow && currentPage <= offsetPage) {
        pages = [1, '…']
        addPages(currentPage - 1, currentPage + limitPageToShow - 2)
        if (totalPage - (currentPage + limitPageToShow - 2) > 1) {
          pages.push('…')
        }
        pages.push(totalPage)
      } else {
        if (offsetPage > 1) {
          pages.push(1, '…')
        }
        addPages(offsetPage, totalPage)
      }
      return pages
    }
  }
})
</script>
<style scoped>
  a.fr-pagination__link[title="Page …"] {
    cursor: default;
  }
</style>
