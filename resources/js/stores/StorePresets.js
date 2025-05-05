import { defineStore } from 'pinia';

export const useTemplatesStore = defineStore('templates', {
  state: () => ({
    templates: [],
    selectedTemplateIndex: null,
    selectedTemplateID: null,
  }),
  
  actions: {
    selectTemplate(index, id) {
      this.selectedTemplateIndex = index;
      this.selectedTemplateID = id;
    },
    
    getSelectedTemplate() {
      return this.templates[this.selectedTemplateIndex];
    }
  }
});