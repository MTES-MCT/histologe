/**
 * Responsive Array - Transform tables into card lists on small screens
 *
 * Usage: Add the class 'fr-table--responsive-array' to the table element
 */

class ResponsiveArray {
  constructor() {
    this.tables = [];
    this.breakpoint = 1248; // Custom breakpoint for responsive cards
    this.init();
  }

  init() {
    // Find all tables with the responsive class
    const responsiveTables = document.querySelectorAll('table.fr-table--responsive-array');

    responsiveTables.forEach((table) => {
      this.tables.push({
        element: table,
        headers: this.extractHeaders(table),
        initialized: false,
      });
    });

    // Setup responsive behavior
    this.handleResize();
    window.addEventListener(
      'resize',
      this.debounce(() => this.handleResize(), 150)
    );
  }

  extractHeaders(table) {
    const headers = [];
    const headerCells = table.querySelectorAll('thead th');

    headerCells.forEach((th, index) => {
      const label = th.textContent.trim();
      headers.push({
        label: label,
        isTitle: th.dataset.responsiveTitle === '1',
        isFullWidth: label.toLowerCase() === 'actions',
      });
    });

    return headers;
  }

  handleResize() {
    const isMobile = window.innerWidth < this.breakpoint;

    this.tables.forEach((tableData) => {
      if (isMobile) {
        this.transformToCards(tableData);
      } else {
        this.restoreTable(tableData);
      }
    });
  }

  transformToCards(tableData) {
    if (tableData.element.classList.contains('responsive-cards-mode')) {
      return; // Already in card mode
    }

    const tbody = tableData.element.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');

    rows.forEach((row) => {
      const cells = row.querySelectorAll('td');
      let titleCellIndex = -1;

      // Find which cell should be the title
      tableData.headers.forEach((header, index) => {
        if (header.isTitle) {
          titleCellIndex = index;
        }
      });

      // Store original content and index for all cells first (only once, never overwrite)
      cells.forEach((cell, index) => {
        if (!cell.dataset.originalContent) {
          cell.dataset.originalContent = cell.innerHTML;
        }
        if (!cell.dataset.originalIndex) {
          cell.dataset.originalIndex = index.toString();
        }
      });

      // Process title cell first if found and move it to the beginning
      if (titleCellIndex !== -1 && cells[titleCellIndex]) {
        const cell = cells[titleCellIndex];

        // Create title element
        const title = document.createElement('div');
        title.className = 'responsive-card-title';
        title.innerHTML = cell.dataset.originalContent;

        cell.innerHTML = '';
        cell.appendChild(title);
        cell.classList.add('responsive-cell', 'responsive-cell-title');

        // Move to first position
        row.insertBefore(cell, row.firstChild);
      }

      // Process all other cells
      cells.forEach((cell, index) => {
        if (index !== titleCellIndex && tableData.headers[index]) {
          // Add header label before content
          const label = document.createElement('div');
          label.className = 'responsive-cell-label';
          label.textContent = tableData.headers[index].label;

          const content = document.createElement('div');
          content.className = 'responsive-cell-content';
          content.innerHTML = cell.dataset.originalContent;

          cell.innerHTML = '';
          cell.appendChild(label);
          cell.appendChild(content);
          cell.classList.add('responsive-cell');

          // Mark full-width cells (like Actions)
          if (tableData.headers[index].isFullWidth) {
            cell.classList.add('responsive-cell-full-width');
          }
        }
      });

      row.classList.add('responsive-card');
    });

    tableData.element.classList.add('responsive-cards-mode');
    tableData.initialized = true;
  }

  restoreTable(tableData) {
    if (!tableData.element.classList.contains('responsive-cards-mode')) {
      return; // Already in table mode
    }

    const tbody = tableData.element.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');

    rows.forEach((row) => {
      const cells = Array.from(row.querySelectorAll('td'));

      // Restore content for all cells
      cells.forEach((cell) => {
        if (cell.dataset.originalContent !== undefined) {
          cell.innerHTML = cell.dataset.originalContent;
          cell.classList.remove(
            'responsive-cell',
            'responsive-cell-title',
            'responsive-cell-full-width'
          );
        }
      });

      // Sort cells back to their original positions
      cells.sort((a, b) => {
        const indexA = parseInt(a.dataset.originalIndex || '0');
        const indexB = parseInt(b.dataset.originalIndex || '0');
        return indexA - indexB;
      });

      // Re-append cells in the correct order
      cells.forEach((cell) => {
        row.appendChild(cell);
      });

      row.classList.remove('responsive-card');
    });

    tableData.element.classList.remove('responsive-cards-mode');
  }

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    new ResponsiveArray();
  });
} else {
  new ResponsiveArray();
}
