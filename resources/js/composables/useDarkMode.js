import { ref, onMounted, watch } from 'vue';

export default function useDarkMode() {
  const isDarkMode = ref(true); // Default to true

  // Initialize dark mode based on user preference or system preference
  const initDarkMode = () => {
    // Check for saved preference or default to dark
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
      isDarkMode.value = false;
      document.documentElement.classList.remove('dark');
    } else {
      // Default to dark mode
      isDarkMode.value = true;
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    }
  };

  // Toggle between light and dark mode
  const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  };

  // Watch for system theme changes if no preference is set
  const setupSystemThemeWatcher = () => {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem('theme')) {
        isDarkMode.value = e.matches;
        if (e.matches) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
      }
    });
  };

  // Initialize on component mount
  onMounted(() => {
    initDarkMode();
    setupSystemThemeWatcher();
  });

  return {
    isDarkMode,
    toggleDarkMode
  };
} 