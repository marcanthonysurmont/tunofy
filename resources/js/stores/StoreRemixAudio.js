import { defineStore } from 'pinia';

export const StoreRemixAudio = defineStore('audio', {
  state: () => ({
    isMuted: false,
  }),
  actions: {
    toggleMute() {
      this.isMuted = !this.isMuted;
      const audios = document.querySelectorAll('audio');
      audios.forEach(audio => {
        audio.muted = this.isMuted;
      });
    },
    setMute(value) {
      this.isMuted = value;
      const audios = document.querySelectorAll('audio');
      audios.forEach(audio => {
        audio.muted = this.isMuted;
      });
    },
  },
});
