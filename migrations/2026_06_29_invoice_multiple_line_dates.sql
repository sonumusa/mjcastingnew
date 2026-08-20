ALTER TABLE invoice_multiple_items
    ADD COLUMN IF NOT EXISTS item_date DATE NULL AFTER invoice_multiple_id;

ALTER TABLE invoice_multiple_receives
    ADD COLUMN IF NOT EXISTS receive_date DATE NULL AFTER invoice_multiple_id;

UPDATE invoice_multiple_items imi
INNER JOIN invoice_multiple im ON im.id = imi.invoice_multiple_id
SET imi.item_date = im.invoice_date
WHERE imi.item_date IS NULL;

UPDATE invoice_multiple_receives imr
INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
SET imr.receive_date = im.invoice_date
WHERE imr.receive_date IS NULL;