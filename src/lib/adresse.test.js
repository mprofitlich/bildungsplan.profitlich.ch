import { describe, expect, it } from 'vitest';
import { ANSICHTEN, ansichtAus, pfad } from './adresse.js';

describe('pfad', () => {
  it('legt «Kompetenzen» auf die Startseite', () => {
    expect(pfad('kompetenzen')).toBe('/');
  });

  it('hängt beim Kurs die Id an', () => {
    expect(pfad('kurs', '3f2a')).toBe('/kurs/3f2a');
  });

  it('findet jede Ansicht unter ihrem Pfad wieder', () => {
    for (const ansicht of ANSICHTEN) expect(ansichtAus(pfad(ansicht))).toEqual({ ansicht });
  });
});

describe('ansichtAus', () => {
  it('liest die Id des Kurses', () => {
    expect(ansichtAus('/kurs/3f2a')).toEqual({ ansicht: 'kurs', kursId: '3f2a' });
  });

  it('nimmt den Kurs auch ohne Id', () => {
    expect(ansichtAus('/kurs')).toEqual({ ansicht: 'kurs' });
  });

  it('übersieht einen Schrägstrich am Ende', () => {
    expect(ansichtAus('/glossar/')).toEqual({ ansicht: 'glossar' });
  });

  it('macht aus einem unbekannten Pfad «Kompetenzen»', () => {
    expect(ansichtAus('/irgendwas')).toEqual({ ansicht: 'kompetenzen' });
  });
});
