<template>
  <div class="fr-grid-row fr-mb-1w fr-grid-row--center">
    <nav role="navigation" class="fr-pagination" aria-label="Pagination">
      <ul class="fr-pagination__list">
        <li>
          <a href="#"
             @click.prevent="pagination.current_page > 1 && $emit('changePage', 1)"
             :aria-disabled="pagination.current_page <= 1"
             class="fr-pagination__link fr-pagination__link--first"
             role="link">Première page </a>
        </li>
        <li>
          <a href="#"
             @click.prevent="pagination.current_page > 1 && $emit('changePage', pagination.current_page - 1)"
             class="fr-pagination__link fr-pagination__link--prev fr-pagination__link--lg-label"
             :aria-disabled="pagination.current_page <= 1"
             role="link"> Page précédente
          </a>
        </li>
        <li v-for="page in pageNumbers" :key="page">
          <a href="#"
             @click.prevent="page !== '...' && $emit('changePage', page)"
             class="fr-pagination__link"
             :class="{ 'fr-pagination__link--active': typeof page === 'number' && page === pagination.current_page }"
             :title="`Page ${page}`"
             :aria-current="typeof page === 'number' && page === pagination.current_page ? 'page' : null">{{ page }}</a>
        </li>

        <li>
          <a href="#"
             @click.prevent="pagination.current_page < pagination.total_pages && $emit('changePage', pagination.current_page + 1)"
             :aria-disabled="pagination.current_page < pagination.total_pages"
             class="fr-pagination__link fr-pagination__link--next fr-pagination__link--lg-label">
            Page suivante </a>
        </li>

        <li>
          <a href="#"
             @click.prevent="pagination.current_page < pagination.total_pages && $emit('changePage', pagination.total_pages)"
             :aria-disabled="pagination.current_page === pagination.total_pages"
             class="fr-pagination__link fr-pagination__link--last" >
            Dernière page </a>
        </li>
      </ul>
    </nav>
  </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TheHistoSignalementListPagination',
  props: {
    pagination: {
      type: Object,
      required: true
    }
  },
  computed: {
    pageNumbers () {
      let pages = []
      const currentPage = this.pagination.current_page
      const totalPage = this.pagination.total_pages
      const limit = 10
      if (currentPage < limit) {
        if (totalPage <= limit) {
          for (let i = 1; i <= totalPage; i++) {
            pages.push(i)
          }
        } else if (totalPage > limit) {
          for (let i = 1; i <= limit; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(this.pagination.total_pages)
        }
      } else {
        if (currentPage >= limit && currentPage < totalPage - limit) {
          pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPage]
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = totalPage - limit; i <= totalPage; i++) {
            pages.push(i)
          }
        }
      }
      return pages
    }
  }
})
</script>

<style scoped>

</style>
