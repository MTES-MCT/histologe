export const variableTester = {
  isNotEmpty<T>(value: T | null | undefined | ''): value is T {
    return value !== undefined && value !== null && value !== ''
  },
  isEmpty (value: any): boolean {
    return value === undefined || value === null || value === ''
  }
}
