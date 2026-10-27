/**
 * Lässt ein Textfeld mit seinem Inhalt wachsen, als Attachment: `{@attach wachsend(() => wert)}`.
 * `rows` bleibt die Mindesthöhe, weil «auto» zuerst darauf zurückfällt. Per Script statt
 * `field-sizing: content`, das noch nicht alle Browser kennen.
 * @param {() => unknown} inhalt Liest den Wert, damit Svelte bei jeder Änderung neu misst.
 * @returns {(feld: HTMLTextAreaElement) => () => void}
 */
export function wachsend(inhalt) {
  return (feld) => {
    inhalt();
    const anpassen = () => {
      feld.style.height = 'auto';
      feld.style.height = `${feld.scrollHeight + feld.offsetHeight - feld.clientHeight}px`;
    };
    anpassen();
    // Mit der Fensterbreite ändert sich der Umbruch.
    addEventListener('resize', anpassen);
    return () => removeEventListener('resize', anpassen);
  };
}
