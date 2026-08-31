{{--
    One line of a payout receipt's workings.

    The parts are assembled here rather than in the service so the whole
    line is translatable: French wants "Honoraires : Appartement 4B" with
    its own spacing around the colon, and a sentence built in PHP could
    not give it that.

    A row carries some of: a label naming what kind of movement it is, a
    free-text description, the place it happened, and either a period or a
    single date. Anything absent is simply left out, so a movement with no
    unit and no invoice still reads as a sentence.
--}}
@php
    $parts = [];

    if (! empty($row['text'])) {
        $parts[] = $row['text'];
    }

    if (! empty($row['place'])) {
        $parts[] = $row['place'];
    }

    if (! empty($row['from']) && ! empty($row['to'])) {
        $parts[] = __('documents.owner_payout_receipt.between', [
            'from' => $formatter->date($row['from']),
            'to' => $formatter->date($row['to']),
        ]);
    } elseif (! empty($row['date'])) {
        $parts[] = $formatter->date($row['date']);
    }

    $detail = implode(' — ', $parts);
@endphp

@if(! empty($row['label']))
    {{ __('documents.owner_payout_receipt.rows.'.$row['label']) }}@if($detail !== ''):@endif
@endif

{{ $detail }}
