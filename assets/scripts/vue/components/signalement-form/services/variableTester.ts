export const variableTester = {
  isNotEmpty (value: any): boolean {
    return value !== undefined && value !== null && value !== ''
  },
  isEmpty (value: any): boolean {
    return value === undefined || value === null || value === ''
  }
}
