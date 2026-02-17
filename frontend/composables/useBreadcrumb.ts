interface BreadcrumbItem {
  label: string
  to?: string
}

let currentId = 0

export function useBreadcrumb() {
  const items = useState<BreadcrumbItem[]>('breadcrumb', () => [])

  function set(crumbs: BreadcrumbItem[]) {
    const id = ++currentId
    items.value = crumbs

    onUnmounted(() => {
      if (currentId === id) {
        items.value = []
      }
    })
  }

  return {
    items: readonly(items),
    set,
  }
}
