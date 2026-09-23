export const TRANSACTION_STATUS = Object.freeze({
    COMPLETED: 'completed',
    VOID: 'void',
});

export const TRANSACTION_STATUS_LABELS = Object.freeze({
    [TRANSACTION_STATUS.COMPLETED]: 'Selesai',
    [TRANSACTION_STATUS.VOID]: 'Dibatalkan',
});
