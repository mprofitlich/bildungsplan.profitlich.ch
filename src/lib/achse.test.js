import { describe, expect, it } from 'vitest';
import { achse } from './achse.js';

describe('achse', () => {
  it('zählt bei kleinen Werten in Einerschritten', () => {
    expect(achse(3)).toEqual({ oben: 3, striche: [0, 1, 2, 3] });
  });

  it('zeigt ohne Werte eine Achse bis 1', () => {
    expect(achse(0)).toEqual({ oben: 1, striche: [0, 1] });
  });

  it('rundet auf runde Schritte auf', () => {
    expect(achse(7)).toEqual({ oben: 8, striche: [0, 2, 4, 6, 8] });
    expect(achse(17)).toEqual({ oben: 20, striche: [0, 5, 10, 15, 20] });
    expect(achse(130)).toEqual({ oben: 150, striche: [0, 50, 100, 150] });
  });
});
