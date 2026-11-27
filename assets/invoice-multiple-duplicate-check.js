(function () {
    const form = document.getElementById('multi-form') || document.getElementById('multi-edit-form');
    const modal = document.getElementById('duplicate-weight-modal');
    if (!form || !modal) return;

    const message = document.getElementById('duplicate-weight-message');
    const cancelButton = document.getElementById('duplicate-weight-cancel');
    const continueButton = document.getElementById('duplicate-weight-continue');
    let pendingSubmitter = null;
    let duplicateInputs = [];
    let allowNextSubmit = false;

    function findCloseWeights(selector, positiveOnly) {
        const values = [];
        form.querySelectorAll(selector).forEach(function (input) {
            const parsed = Number(input.value);
            if (!Number.isFinite(parsed) || (positiveOnly && parsed <= 0)) return;

            const value = Number(parsed.toFixed(3));
            if (!Number.isFinite(value) || value === 0) return;
            const milliGrams = Math.round(value * 1000);
            if (!Number.isFinite(milliGrams) || milliGrams === 0) return;
            values.push({ milliGrams: milliGrams, input: input });
        });

        values.sort(function (a, b) { return a.milliGrams - b.milliGrams; });
        const pairs = new Map();
        for (let i = 0; i < values.length; i++) {
            for (let j = i + 1; j < values.length; j++) {
                const difference = values[j].milliGrams - values[i].milliGrams;
                if (difference > 1000) break;

                const key = values[i].milliGrams + ':' + values[j].milliGrams;
                if (!pairs.has(key)) {
                    pairs.set(key, {
                        first: values[i].milliGrams / 1000,
                        second: values[j].milliGrams / 1000,
                        difference: difference / 1000,
                        inputs: []
                    });
                }
                const pair = pairs.get(key);
                pair.inputs.push(values[i].input, values[j].input);
            }
        }

        return Array.from(pairs.values());
    }

    function closeModal(focusInput) {
        modal.hidden = true;
        pendingSubmitter = null;
        if (focusInput) focusInput.focus();
    }

    form.addEventListener('submit', function (event) {
        if (allowNextSubmit) {
            allowNextSubmit = false;
            return;
        }

        const castingDuplicates = findCloseWeights('#calc-rows input[name$="[casting_weight]"]', false);
        const receivingDuplicates = findCloseWeights('#receives-container input[name$="[gross_weight]"]', true);
        if (!castingDuplicates.length && !receivingDuplicates.length) return;

        event.preventDefault();
        pendingSubmitter = event.submitter || null;
        duplicateInputs = Array.from(new Set(castingDuplicates.concat(receivingDuplicates)
            .flatMap(function (pair) { return pair.inputs; })));

        function describePairs(pairs) {
            return pairs.map(function (pair) {
                if (pair.difference === 0) {
                    return pair.first.toFixed(3) + ' g repeated';
                }
                return pair.first.toFixed(3) + ' and ' + pair.second.toFixed(3)
                    + ' g (difference ' + pair.difference.toFixed(3) + ' g)';
            }).join('; ');
        }

        const sections = [];
        if (castingDuplicates.length) {
            sections.push('Casting gross weights within 1.000 g: ' + describePairs(castingDuplicates));
        }
        if (receivingDuplicates.length) {
            sections.push('Receiving gross weights within 1.000 g: ' + describePairs(receivingDuplicates));
        }

        message.textContent = sections.join('. ') + '. You can cancel and correct the rows, or process anyway.';
        modal.hidden = false;
        continueButton.focus();
    });

    cancelButton.addEventListener('click', function () {
        closeModal(duplicateInputs[0]);
        duplicateInputs = [];
    });

    continueButton.addEventListener('click', function () {
        const submitter = pendingSubmitter;
        modal.hidden = true;
        pendingSubmitter = null;

        if (typeof form.requestSubmit === 'function') {
            allowNextSubmit = true;
            if (submitter) form.requestSubmit(submitter);
            else form.requestSubmit();
            return;
        }

        if (submitter && submitter.name) {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = submitter.name;
            actionInput.value = submitter.value;
            form.appendChild(actionInput);
        }
        form.submit();
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal(duplicateInputs[0]);
            duplicateInputs = [];
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal(duplicateInputs[0]);
            duplicateInputs = [];
        }
    });
})();
