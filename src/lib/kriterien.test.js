import { describe, expect, it } from 'vitest';
import { kriterienVon } from './kriterien.js';

describe('kriterienVon', () => {
  it('ergänzt fehlende Kriterien leer, etwa bei einem Kurs von vor den Kriterien', () => {
    expect(kriterienVon(undefined)).toEqual({ entwicklung: '', gestaltungskonzept: '', umsetzung: '', vermittlung: '' });
  });

  it('behält vorhandenen Text und lässt Unbekanntes und Nicht-Text weg', () => {
    expect(kriterienVon({ vermittlung: 'Probebewerbung', umsetzung: 3, erfunden: 'x' })).toEqual({
      entwicklung: '',
      gestaltungskonzept: '',
      umsetzung: '',
      vermittlung: 'Probebewerbung',
    });
  });
});
