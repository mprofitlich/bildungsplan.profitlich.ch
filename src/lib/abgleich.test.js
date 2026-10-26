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
});
