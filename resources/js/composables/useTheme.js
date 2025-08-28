import { ref, watch, computed } from 'vue'

// Estado global do tema
const isDark = ref(false)
const isInitialized = ref(false)

export function useTheme() {
  const initializeTheme = () => {
    if (isInitialized.value) return

    // Verificar preferência salva no localStorage
    const savedTheme = localStorage.getItem('theme')

    if (savedTheme) {
      isDark.value = savedTheme === 'dark'
    } else {
      // Usar preferência do sistema se não houver configuração salva
      isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }

    // Aplicar tema inicial
    applyTheme()
    isInitialized.value = true
  }

  const applyTheme = () => {
    const html = document.documentElement

    if (isDark.value) {
      html.classList.add('dark')
    } else {
      html.classList.remove('dark')
    }
  }

  const toggleTheme = () => {
    isDark.value = !isDark.value
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    applyTheme()
  }

  const setTheme = (theme) => {
    isDark.value = theme === 'dark'
    localStorage.setItem('theme', theme)
    applyTheme()
  }

  const currentTheme = computed(() => isDark.value ? 'dark' : 'light')

  // Watch para mudanças no tema
  watch(isDark, () => {
    if (isInitialized.value) {
      applyTheme()
    }
  })

  // Escutar mudanças na preferência do sistema
  if (typeof window !== 'undefined') {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    mediaQuery.addListener((e) => {
      // Só aplicar se não houver configuração manual salva
      if (!localStorage.getItem('theme')) {
        isDark.value = e.matches
      }
    })
  }

  return {
    isDark: computed(() => isDark.value),
    currentTheme,
    toggleTheme,
    setTheme,
    initializeTheme
  }
}

// Inicialização automática para SSR safety
if (typeof window !== 'undefined') {
  const { initializeTheme } = useTheme()
  initializeTheme()
}
