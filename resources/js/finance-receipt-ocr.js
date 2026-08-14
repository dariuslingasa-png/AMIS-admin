const normalize = (value) => String(value || '').replace(/\s+/g, ' ').trim();

const referenceFromValue = (value) => {
    const source = String(value || '').toUpperCase().trim();
    const tokens = source.match(/\b[A-Z0-9][A-Z0-9-]{5,39}\b/g) || [];
    const labeledToken = tokens.find((token) => /[A-Z]/.test(token) && /\d/.test(token));
    if (labeledToken) return labeledToken;

    const spacedDigits = source.match(/\b(?:\d[\s-]?){8,24}\b/)?.[0];
    if (spacedDigits) return spacedDigits.replace(/[\s-]+/g, '');

    return tokens.find((token) => /\d/.test(token)) || null;
};

const receiptLines = (rawText) => String(rawText || '')
    .split(/\r?\n/)
    .map(normalize)
    .filter(Boolean);

const extractLabeledIdentifier = (lines, labels) => {
    const label = new RegExp(`(?:${labels})\\s*(?:no\\.?|number|id|code)?\\s*[:#-]?\\s*(.*)$`, 'i');

    for (let index = 0; index < lines.length; index += 1) {
        const match = lines[index].match(label);
        if (!match) continue;

        const sameLine = referenceFromValue(match[1]);
        if (sameLine) return sameLine;

        const nextLine = referenceFromValue(lines[index + 1]);
        if (nextLine) return nextLine;
    }

    return null;
};

const extractIdentifiers = (rawText) => {
    const lines = receiptLines(rawText);
    const referenceNumber = extractLabeledIdentifier(lines, 'reference|ref(?:erence)?');
    const transactionNumber = extractLabeledIdentifier(lines, 'transaction|txn|trace|confirmation|receipt');

    // Last resort for common 12–24 digit Maya/GCash transaction references.
    // Eleven-digit Philippine mobile numbers are intentionally excluded.
    const compactDigits = String(rawText || '').replace(/(?<=\d)[\s-](?=\d)/g, '');
    const fallback = (compactDigits.match(/\b\d{12,24}\b/g) || [])
        .filter((candidate) => !/^09\d{9}$/.test(candidate))
        .sort((left, right) => right.length - left.length)[0] || null;

    return {
        referenceNumber,
        transactionNumber,
        value: referenceNumber || transactionNumber || fallback,
        source: referenceNumber ? 'Reference number' : (transactionNumber ? 'Transaction ID' : (fallback ? 'Transaction number' : null)),
    };
};

const parseReceipt = (rawText) => {
    const text = normalize(rawText);
    const identifiers = extractIdentifiers(rawText);
    const reference = identifiers.value;
    const amountText = text.match(/(?:amount\s*(?:paid|sent|transferred)?|total)\s*[:#-]?\s*(?:₱|php)?\s*([\d,]+(?:\.\d{2})?)/i)?.[1]
        || text.match(/(?:₱|php)\s*([\d,]+(?:\.\d{2})?)/i)?.[1];
    const amount = amountText ? Number(amountText.replaceAll(',', '')) : null;
    const numericDate = text.match(/\b(20\d{2})[\/-](\d{1,2})[\/-](\d{1,2})\b/) || text.match(/\b(\d{1,2})[\/-](\d{1,2})[\/-](20\d{2})\b/);
    let date = null;
    if (numericDate) {
        date = numericDate[1].length === 4
            ? `${numericDate[1]}-${String(numericDate[2]).padStart(2, '0')}-${String(numericDate[3]).padStart(2, '0')}`
            : `${numericDate[3]}-${String(numericDate[1]).padStart(2, '0')}-${String(numericDate[2]).padStart(2, '0')}`;
    }
    const person = (labels) => text.match(new RegExp(`(?:${labels})\\s*[:#-]?\\s*([A-Z][A-Z .,'-]{2,50})`, 'i'))?.[1]?.trim() || null;
    const provider = /\bgcash\b/i.test(text) ? 'gcash' : /\b(?:maya|paymaya)\b/i.test(text) ? 'maya' : /\bbdo\b/i.test(text) ? 'bdo' : null;
    const receiptSignals = ['successful', 'successfully', 'transaction details', 'reference number', 'amount sent', 'amount paid', 'sent to', 'paid to'].filter((term) => text.toLowerCase().includes(term)).length;
    const nonReceiptSignals = ['statement of account', 'schedule of fees', 'tuition fee', 'school year'].filter((term) => text.toLowerCase().includes(term)).length;

    return {
        reference,
        identifierSource: identifiers.source,
        amount,
        date,
        provider,
        sender: person('from|sender|sent by|remitter'),
        receiver: person('to|recipient|receiver|beneficiary|paid to'),
        documentType: nonReceiptSignals > 0 ? 'not_receipt' : (receiptSignals > 0 || [reference, amount, date].filter(Boolean).length >= 2 ? 'receipt' : 'uncertain'),
        rawText: String(rawText || ''),
    };
};

const formatFinanceAmount = (value) => {
    const cleaned = String(value || '')
        .replaceAll(',', '')
        .replace(/[^\d.]/g, '');

    if (!cleaned) return '';

    const [rawInteger, ...fractionParts] = cleaned.split('.');
    const integer = (rawInteger || '0').replace(/^0+(?=\d)/, '');
    const groupedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = fractionParts.join('').slice(0, 2);

    return cleaned.includes('.') ? `${groupedInteger}.${fraction}` : groupedInteger;
};

const prepareFinanceOnsiteForm = () => {
    const proof = document.getElementById('financePaymentProofSection');
    const firstDetailRow = document.getElementById('financePaymentDetailFields');
    if (proof && firstDetailRow) firstDetailRow.before(proof);

    [
        'financeAmountInput',
        'financeTransactionAtInput',
        'financeReferenceInput',
        'financeAccountReceivedInput',
    ].forEach((id) => {
        const field = document.getElementById(id);
        if (field) field.value = '';
    });
};

const syncFinanceSubmitState = () => {
    const submit = document.getElementById('financeOnsiteSubmit');
    if (!submit) return;
    submit.disabled = ['ocrBusy', 'duplicateBusy', 'duplicateFound']
        .some((key) => submit.dataset[key] === 'true');
};

const setupFinanceAmountInput = () => {
    const input = document.getElementById('financeAmountInput');
    const form = document.getElementById('financeOnsiteForm');
    if (!input || !form) return;

    const applyFormatting = () => {
        const cursor = input.selectionStart ?? input.value.length;
        const logicalCharactersBeforeCursor = input.value
            .slice(0, cursor)
            .replaceAll(',', '')
            .replace(/[^\d.]/g, '')
            .length;

        input.value = formatFinanceAmount(input.value);

        let logicalCharactersSeen = 0;
        let nextCursor = input.value.length;
        for (let index = 0; index < input.value.length; index += 1) {
            if (input.value[index] !== ',') logicalCharactersSeen += 1;
            if (logicalCharactersSeen >= logicalCharactersBeforeCursor) {
                nextCursor = index + 1;
                break;
            }
        }

        input.setSelectionRange(nextCursor, nextCursor);
    };

    input.value = formatFinanceAmount(input.value);
    input.addEventListener('input', applyFormatting);
    input.addEventListener('blur', () => {
        input.value = formatFinanceAmount(input.value).replace(/\.$/, '');
    });
    form.addEventListener('submit', () => {
        input.value = input.value.replaceAll(',', '');
    });
};

const setupFinanceOcr = () => {
    const input = document.getElementById('financeReceiptInput');
    const panel = document.getElementById('financeOcrStatus');
    const extractedFields = document.getElementById('financeOcrFields');
    const amountDisplay = document.getElementById('financeOcrAmountDisplay');
    const referenceDisplay = document.getElementById('financeOcrReferenceDisplay');
    const submit = document.getElementById('financeOnsiteSubmit');
    if (!input || !panel || !submit) return;

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            panel.textContent = 'PDF is disabled. Upload a JPG, JPEG, or PNG screenshot.';
            panel.className = 'mt-3 rounded-lg bg-rose-100 p-3 text-xs font-bold text-rose-800';
            input.value = '';
            window.dispatchEvent(new CustomEvent('finance-file-cleared'));
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            panel.textContent = 'This screenshot is larger than 10 MB. Choose a smaller JPG, JPEG, or PNG image.';
            panel.className = 'mt-3 rounded-lg bg-rose-100 p-3 text-xs font-bold text-rose-800';
            input.value = '';
            window.dispatchEvent(new CustomEvent('finance-file-cleared'));
            return;
        }

        submit.dataset.ocrBusy = 'true';
        syncFinanceSubmitState();
        extractedFields?.classList.add('hidden');
        extractedFields?.classList.remove('grid');
        panel.textContent = 'Quality check complete. Running Tesseract OCR fallback…';
        panel.className = 'mt-3 rounded-lg bg-blue-100 p-3 text-xs font-bold text-blue-800';

        try {
            const { createWorker } = await import('tesseract.js');
            const worker = await createWorker('eng', 1, {
                logger(message) {
                    if (message.status === 'recognizing text') panel.textContent = `Reading payment proof… ${Math.round((message.progress || 0) * 100)}%`;
                },
            });
            const result = await worker.recognize(file, { rotateAuto: true });
            await worker.terminate();
            const parsed = parseReceipt(result.data.text);

            document.getElementById('financeOcrRaw').value = parsed.rawText;
            document.getElementById('financeOcrConfidence').value = Number(result.data.confidence || 0) / 100;
            document.getElementById('financeOcrSender').value = parsed.sender || '';
            document.getElementById('financeOcrReceiver').value = parsed.receiver || '';
            document.getElementById('financeOcrType').value = parsed.documentType;
            document.getElementById('financeOcrReference').value = parsed.reference || '';
            document.getElementById('financeOcrAmount').value = parsed.amount || '';

            const reference = document.querySelector('[name="reference_number"]');
            const amount = document.querySelector('[name="amount"]');
            const receivingAccount = document.querySelector('[name="account_received"]');
            const fillIfEmpty = (field, value) => {
                if (!field || value === null || value === undefined || value === '' || field.value.trim()) return;
                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            };

            fillIfEmpty(reference, parsed.reference);
            fillIfEmpty(amount, parsed.amount ? parsed.amount.toFixed(2) : null);
            fillIfEmpty(receivingAccount, parsed.receiver);

            if (parsed.provider) {
                window.dispatchEvent(new CustomEvent('finance-provider-detected', { detail: parsed.provider }));
            }

            if (amountDisplay) amountDisplay.textContent = parsed.amount ? `₱${parsed.amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'Not found';
            if (referenceDisplay) referenceDisplay.textContent = parsed.reference
                ? `${parsed.reference} · ${parsed.identifierSource}`
                : 'Not found — enter manually';
            extractedFields?.classList.remove('hidden');
            extractedFields?.classList.add('grid');

            if (parsed.documentType === 'not_receipt') {
                panel.textContent = 'This image looks like an SOA or fee document, not a payment receipt. Upload the actual transaction screenshot.';
                panel.className = 'mt-3 rounded-lg bg-rose-100 p-3 text-xs font-bold text-rose-800';
                return;
            }

            panel.textContent = parsed.documentType === 'receipt'
                ? (parsed.reference
                    ? `Payment receipt detected · OCR confidence ${Number(result.data.confidence || 0).toFixed(0)}%. Amount and ${parsed.identifierSource?.toLowerCase() || 'transaction number'} were auto-filled when blank.`
                    : `Payment receipt detected · OCR confidence ${Number(result.data.confidence || 0).toFixed(0)}%, but no reference or transaction number was readable. Enter either one manually and add an OCR correction reason.`)
                : 'OCR completed with uncertain fields. Check the amount and transaction/reference number carefully before confirming.';
            panel.className = parsed.documentType === 'receipt'
                ? 'mt-3 rounded-lg bg-emerald-100 p-3 text-xs font-bold text-emerald-800'
                : 'mt-3 rounded-lg bg-amber-100 p-3 text-xs font-bold text-amber-800';
            submit.dataset.ocrBusy = 'false';
            syncFinanceSubmitState();
        } catch (error) {
            panel.textContent = 'OCR could not finish. Finance may still validate the original screenshot manually; this exception will be recorded.';
            panel.className = 'mt-3 rounded-lg bg-amber-100 p-3 text-xs font-bold text-amber-800';
            document.getElementById('financeOcrType').value = 'ocr_unavailable';
            submit.dataset.ocrBusy = 'false';
            syncFinanceSubmitState();
        }
    });
};

const setupFinanceDuplicateCheck = () => {
    const form = document.getElementById('financeOnsiteForm');
    const reference = document.getElementById('financeReferenceInput');
    const receipt = document.getElementById('financeReceiptInput');
    const status = document.getElementById('financeDuplicateStatus');
    const submit = document.getElementById('financeOnsiteSubmit');
    const amount = document.getElementById('financeAmountInput');
    const modal = document.getElementById('financeDuplicateModal');
    const modalReference = document.getElementById('financeDuplicateModalReference');
    const modalAmount = document.getElementById('financeDuplicateModalAmount');
    const modalSource = document.getElementById('financeDuplicateModalSource');
    const replaceReceipt = document.getElementById('financeDuplicateReplaceReceipt');
    const endpoint = form?.dataset.duplicateUrl;
    if (!form || !reference || !receipt || !status || !submit || !modal || !endpoint) return;

    let receiptHash = '';
    let debounceTimer = null;
    let requestSequence = 0;
    let lastModalKey = '';

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    const openModal = (result) => {
        const modalKey = `${result.code || 'duplicate'}:${result.match?.source || ''}:${result.match?.record_id || ''}`;
        if (lastModalKey === modalKey) return;
        lastModalKey = modalKey;

        if (modalReference) modalReference.textContent = reference.value.trim() || 'Matched receipt image';
        if (modalAmount) {
            const numericAmount = Number(String(amount?.value || '').replaceAll(',', ''));
            modalAmount.textContent = numericAmount > 0
                ? `₱${numericAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                : 'Not entered yet';
        }
        if (modalSource) {
            const source = result.match?.source || 'Existing payment record';
            const recordStatus = result.match?.status
                ? ` · ${String(result.match.status).replaceAll('_', ' ').toLowerCase()}`
                : '';
            modalSource.textContent = `${source}${recordStatus}`;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => replaceReceipt?.focus(), 0);
    };

    const setState = (state, message = '') => {
        submit.dataset.duplicateBusy = state === 'checking' ? 'true' : 'false';
        submit.dataset.duplicateFound = state === 'duplicate' ? 'true' : 'false';
        syncFinanceSubmitState();

        if (!message || state === 'hidden') {
            status.textContent = '';
            status.className = 'mt-2.5 hidden rounded-lg border px-3 py-2.5 text-xs font-bold';
            return;
        }

        const colors = state === 'duplicate'
            ? 'border-rose-200 bg-rose-50 text-rose-800'
            : (state === 'clear'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-slate-200 bg-slate-50 text-slate-600');
        status.textContent = message;
        status.className = `mt-2.5 rounded-lg border px-3 py-2.5 text-xs font-bold ${colors}`;
    };

    const checkDuplicate = async () => {
        if (reference.disabled) {
            setState('hidden');
            return;
        }

        const referenceNumber = reference.value.trim();
        if (!referenceNumber && !receiptHash) {
            setState('hidden');
            return;
        }

        const sequence = ++requestSequence;
        setState('checking', 'Checking all AMIS payment records for duplicates.');

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    reference_number: referenceNumber || null,
                    receipt_hash: receiptHash || null,
                }),
            });
            const result = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok) throw new Error(result.message || 'Duplicate check failed.');

            if (result.duplicate) {
                const source = result.match?.source ? ` Existing record: ${result.match.source}.` : '';
                setState('duplicate', `${result.message}${source} Confirm payment is disabled.`);
                openModal(result);
                return;
            }

            setState('clear', 'No duplicate transaction reference or receipt image found.');
        } catch (error) {
            if (sequence !== requestSequence) return;
            setState('error', 'Live duplicate check is unavailable. The server will check again before saving.');
        }
    };

    const scheduleCheck = () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(checkDuplicate, 400);
    };

    reference.addEventListener('input', scheduleCheck);
    reference.addEventListener('change', checkDuplicate);
    new MutationObserver(() => {
        if (reference.disabled) setState('hidden');
        else scheduleCheck();
    }).observe(reference, { attributes: true, attributeFilter: ['disabled'] });

    receipt.addEventListener('change', async () => {
        const file = receipt.files?.[0];
        receiptHash = '';
        if (file && window.crypto?.subtle) {
            const digest = await window.crypto.subtle.digest('SHA-256', await file.arrayBuffer());
            receiptHash = Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
        }
        checkDuplicate();
    });

    form.addEventListener('submit', (event) => {
        if (submit.dataset.duplicateFound === 'true' || submit.dataset.duplicateBusy === 'true') {
            event.preventDefault();
            status.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    modal.querySelectorAll('[data-finance-duplicate-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
    replaceReceipt?.addEventListener('click', () => {
        closeModal();
        requestSequence += 1;
        lastModalKey = '';
        receiptHash = '';
        receipt.value = '';
        reference.value = '';
        [
            amount,
            document.getElementById('financeTransactionAtInput'),
            document.getElementById('financeAccountReceivedInput'),
        ].forEach((field) => {
            if (field) field.value = '';
        });
        [
            'financeOcrRaw',
            'financeOcrConfidence',
            'financeOcrSender',
            'financeOcrReceiver',
            'financeOcrType',
            'financeOcrReference',
            'financeOcrAmount',
        ].forEach((id) => {
            const field = document.getElementById(id);
            if (field) field.value = '';
        });
        const ocrStatus = document.getElementById('financeOcrStatus');
        if (ocrStatus) {
            ocrStatus.textContent = 'Upload a screenshot to run the image quality check and OCR.';
            ocrStatus.className = 'mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs leading-5 text-slate-600';
        }
        const ocrFields = document.getElementById('financeOcrFields');
        ocrFields?.classList.add('hidden');
        ocrFields?.classList.remove('grid');
        submit.dataset.ocrBusy = 'false';
        window.dispatchEvent(new CustomEvent('finance-file-cleared'));
        setState('hidden');
        receipt.click();
    });

    if (reference.value.trim()) scheduleCheck();
};

document.addEventListener('DOMContentLoaded', () => {
    prepareFinanceOnsiteForm();
    setupFinanceAmountInput();
    setupFinanceDuplicateCheck();
    setupFinanceOcr();
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) prepareFinanceOnsiteForm();
});
