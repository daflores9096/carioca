/** Clases de icono Bootstrap Icons según columna activa y dirección. */
export function sortIconClass(sort, dir, column) {
  const icon =
    sort !== column
      ? 'bi-arrow-down-up'
      : dir === 'asc'
        ? 'bi-caret-up-fill'
        : 'bi-caret-down-fill'
  return ['bi', 'sort-th-icon', icon]
}

export function sortThClass(sort, column, align = 'start') {
  return [
    'sort-th',
    'user-select-none',
    align === 'end' ? 'text-end' : '',
    sort === column ? 'sort-th-active' : '',
  ]
    .filter(Boolean)
    .join(' ')
}
