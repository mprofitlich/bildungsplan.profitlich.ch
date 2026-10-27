import { describe, expect, it } from 'vitest';
import { aenderungen, inhalt } from './abgleich.svelte.js';

const kurs = (id, titel = 'Typografie') => ({ id, titel, beschrieb: '', ziele: [] });

describe('aenderungen', () => {
  it('erkennt neue, geänderte und entfernte Kurse', () => {
    const gespeichert = new Map([
      ['a', inhalt(kurs('a'))],
      ['b', inhalt(kurs('b'))],
      ['c', inhalt(kurs('c'))],
    ]);
    expect(aenderungen([kurs('a'), kurs('b', 'Neu'), kurs('d')], gespeichert)).toEqual({
      neu: ['d'],
      geaendert: ['b'],
      entfernt: ['c'],
    });
  });

  it('übersieht Felder, die nur der Server setzt', () => {
    const gespeichert = new Map([['a', inhalt(kurs('a'))]]);
    expect(aenderungen([{ ...kurs('a'), version: 4, sperre: null }], gespeichert).geaendert).toEqual([]);
  });

  it('zählt einen geänderten Text in den Beurteilungskriterien als Änderung', () => {
    const vorher = { ...kurs('a'), kriterien: { vermittlung: '' } };
    const gespeichert = new Map([['a', inhalt(vorher)]]);
    const nachher = { ...vorher, kriterien: { vermittlung: 'Probebewerbung' } };
    expect(aenderungen([nachher], gespeichert).geaendert).toEqual(['a']);
  });

  it('übersieht die Reihenfolge der Kriterien und fehlende Kriterien', () => {
    const vomServer = { ...kurs('a'), kriterien: { vermittlung: 'x', entwicklung: '' } };
    const imBrowser = { ...kurs('a'), kriterien: { entwicklung: '', vermittlung: 'x' } };
    expect(inhalt(imBrowser)).toBe(inhalt(vomServer));
    expect(inhalt(kurs('a'))).toBe(inhalt({ ...kurs('a'), kriterien: {} }));
  });

  // Der Effekt in `starten` hängt nur von dem ab, was hier gelesen wird.
  it('liest auch den Inhalt eines Kurses, der noch nicht gespeichert ist', () => {
    let gelesen = false;
    const neu = {
      ...kurs('a'),
      get titel() {
        gelesen = true;
        return 'Typografie';
      },
    };
    aenderungen([neu], new Map());
    expect(gelesen).toBe(true);
  });
});
