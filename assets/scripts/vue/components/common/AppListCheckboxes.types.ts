export interface CheckboxOption {
  Id: string
  Text: string
}

export interface CheckboxGroup {
  title?: string
  options: CheckboxOption[]
}
