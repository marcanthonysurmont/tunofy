import { defineStore } from 'pinia';

export const useTemplatesStore = defineStore('templates', {
  state: () => ({
    templates: [],
    selectedTemplateIndex: 0
  }),
  
  actions: {
    selectTemplate(index) {
      this.selectedTemplateIndex = index;
    },
    
    getSelectedTemplate() {
      return this.templates[this.selectedTemplateIndex];
    }
  }
});