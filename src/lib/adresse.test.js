import { describe, expect, it } from 'vitest';
import { ANSICHTEN, ansichtAus, folgen, kursZu, pfad, slug } from './adresse.js';

const lokal = { id: '3f2a6b1c-0d4e-4f5a-8b9c-1d2e3f4a5b6c', titel: 'Typografie', beschrieb: '', ziele: [] };
const gespeichert = { ...lokal, id: 'aaaaaaaa-0000-4000-8000-000000000001', nummer: 42 };

describe('slug', () => {
  it('ersetzt Umlaute und Sonderzeichen', () => {
    expect(slug('Schrift & Gestaltung – Übungen')).toBe('schrift-gestaltung-uebungen');
  });

  it('entfernt Akzente', () => {
    expect(slug('Mise en page à la Cézanne')).toBe('mise-en-page-a-la-cezanne');
  });

  it('kürzt lange Titel ohne Bindestrich am Ende', () => {
    expect(slug(`${'a'.repeat(59)} b`)).toBe('a'.repeat(59));
  });
});

describe('pfad', () => {
  it('legt «Kompetenzen» auf die Startseite', () => {
    expect(pfad('kompetenzen')).toBe('/');
  });

  it('nimmt beim Kurs auf dem Server die Nummer', () => {
    expect(pfad('kurs', gespeichert)).toBe('/kurs/42-typografie');
  });

  it('nimmt beim Kurs nur im Browser die Id', () => {
    expect(pfad('kurs', lokal)).toBe(`/kurs/${lokal.id}-typografie`);
  });

  it('lässt den Slug weg, wenn der Titel keinen ergibt', () => {
    expect(pfad('kurs', { ...gespeichert, titel: '' })).toBe('/kurs/42');
  });

  it('findet jede Ansicht unter ihrem Pfad wieder', () => {
    for (const ansicht of ANSICHTEN) expect(ansichtAus(pfad(ansicht))).toEqual({ ansicht });
  });
});

describe('ansichtAus', () => {
  it('liest die Nummer und übergeht den Slug', () => {
    expect(ansichtAus('/kurs/42-alter-titel')).toEqual({ ansicht: 'kurs', kennung: '42' });
    expect(ansichtAus('/kurs/42')).toEqual({ ansicht: 'kurs', kennung: '42' });
  });

  it('trennt die Id vom Slug, obwohl beide Bindestriche enthalten', () => {
    expect(ansichtAus(`/kurs/${lokal.id}-alter-titel`)).toEqual({ ansicht: 'kurs', kennung: lokal.id });
  });

  it('nimmt einen Slug, der mit einer Zahl beginnt, nicht als Teil der Nummer', () => {
    expect(ansichtAus('/kurs/42-2-semester')).toEqual({ ansicht: 'kurs', kennung: '42' });
  });

  it('erkennt keine Kennung in einem Abschnitt ohne Nummer oder Id', () => {
    expect(ansichtAus('/kurs/typografie')).toEqual({ ansicht: 'kurs' });
  });

  it('nimmt den Kurs auch ohne Kennung', () => {
    expect(ansichtAus('/kurs')).toEqual({ ansicht: 'kurs' });
  });

  it('übersieht einen Schrägstrich am Ende', () => {
    expect(ansichtAus('/glossar/')).toEqual({ ansicht: 'glossar' });
  });

  it('macht aus einem unbekannten Pfad «Kompetenzen»', () => {
    expect(ansichtAus('/irgendwas')).toEqual({ ansicht: 'kompetenzen' });
  });
});

describe('folgen', () => {
  function klick(art = {}) {
    const event = { button: 0, metaKey: false, ctrlKey: false, shiftKey: false, altKey: false, ...art };
    let verhindert = false;
    let gewechselt = false;
    folgen({ ...event, preventDefault: () => (verhindert = true) }, () => (gewechselt = true));
    return { verhindert, gewechselt };
  }

  it('wechselt bei einem einfachen Klick in der Anwendung', () => {
    expect(klick()).toEqual({ verhindert: true, gewechselt: true });
  });

  it('überlässt Klicks mit Zusatztaste oder mittlerer Maustaste dem Browser', () => {
    for (const art of [{ metaKey: true }, { ctrlKey: true }, { shiftKey: true }, { button: 1 }]) {
      expect(klick(art)).toEqual({ verhindert: false, gewechselt: false });
    }
  });
});

describe('kursZu', () => {
  it('findet einen Kurs über die Nummer und über die Id', () => {
    expect(kursZu([lokal, gespeichert], '42')).toBe(gespeichert);
    expect(kursZu([lokal, gespeichert], gespeichert.id)).toBe(gespeichert);
    expect(kursZu([lokal, gespeichert], lokal.id)).toBe(lokal);
  });

  it('findet nichts zu einer unbekannten Kennung', () => {
    expect(kursZu([lokal, gespeichert], '7')).toBeUndefined();
  });
});
