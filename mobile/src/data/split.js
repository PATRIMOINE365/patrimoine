/*
 * How an expense bill is shared between co-owners - the largest-remainder
 * proration the server uses, so the preview on the Review page matches the
 * bills that are recorded, to the unit.
 *
 * Per line: each owner gets the floor of amount x share / total share; the
 * units lost to flooring go, one each, to the owners with the largest
 * fractional parts. Whole units in, whole units out, and the shares of one
 * line always add up to that line.
 */

export function splitShares(lines, owners) {
    const totalPct = owners.reduce((sum, o) => sum + Number(o.ownership_percentage ?? 0), 0) || 100;
    const shares = owners.map(() => 0);

    for (const line of lines) {
        const amount = Math.trunc(Number(line.amount || 0));
        const raw = owners.map((o) => (amount * Number(o.ownership_percentage ?? 0)) / totalPct);
        const floors = raw.map((v) => Math.floor(v));
        let remainder = amount - floors.reduce((s, v) => s + v, 0);
        const order = raw.map((v, i) => [v - floors[i], i]).sort((a, b) => b[0] - a[0] || a[1] - b[1]);

        for (const [, i] of order) {
            if (remainder <= 0) {
                break;
            }

            floors[i] += 1;
            remainder -= 1;
        }

        floors.forEach((v, i) => { shares[i] += v; });
    }

    return shares;
}
