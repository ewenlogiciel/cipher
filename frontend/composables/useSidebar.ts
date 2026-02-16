const collapsed = ref(false)

export function useSidebar() {
  function toggle() {
    collapsed.value = !collapsed.value
  }

  return {
    collapsed: readonly(collapsed),
    toggle,
  }
}
