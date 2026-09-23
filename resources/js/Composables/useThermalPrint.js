/**
 * Composable untuk menangani pencetakan receipt menggunakan print standar browser.
 * Sesuai PRD MVP: Menggunakan kemampuan print standar browser / receipt digital.
 */
export function useThermalPrint() {
    const printReceipt = () => {
        window.print();
    };

    return {
        printReceipt,
    };
}
