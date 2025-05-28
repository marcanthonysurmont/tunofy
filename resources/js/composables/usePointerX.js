export function usePointerX() {
  function calculateClientX(event) {
    if (event.touches && event.touches.length > 0) {
      return event.touches[0].clientX;
    } else if (event.changedTouches && event.changedTouches.length > 0) {
      return event.changedTouches[0].clientX;
    } else {
      return event.clientX;
    }
  }

  return { calculateClientX };
}
