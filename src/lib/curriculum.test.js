import { describe, expect, it } from 'vitest';
import { ziel } from './bildungsplan.js';
import { summeJeKompetenz } from './curriculum.js';

describe('summeJeKompetenz', () => {
  it('zählt ein Ziel in mehreren Kursen mehrfach', () => {
    const kompetenz = ziel('a1.1-schule').kompetenz;
    const summe = summeJeKompetenz([{ ziele: ['a1.1-schule'] }, { ziele: ['a1.1-schule', 'a1.1-betrieb'] }]);
    expect(summe[kompetenz]).toBe(3);
  });

  it('trennt nach Handlungskompetenz und übergeht unbekannte Ids', () => {
    const summe = summeJeKompetenz([{ ziele: ['a1.1-schule', 'g3.3-schule', 'gibt-es-nicht'] }]);
    expect(Object.values(summe)).toEqual([1, 1]);
  });
});
