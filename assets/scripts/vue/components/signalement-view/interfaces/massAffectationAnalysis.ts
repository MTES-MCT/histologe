// Signalement tel que renvoyé par MassAffectationController::analyze()
export interface MassAffectationSignalement {
  id: number
  reference: string | null
  identite_occupant: string
  adresse: string
}

// Format renvoyé par MassAffectationController::analyze()
export interface MassAffectationAnalysis {
  signalementsToAffect: MassAffectationSignalement[]
  signalementsAlreadyAffected: MassAffectationSignalement[]
  signalementsOutOfPerimeter: MassAffectationSignalement[]
  partnerInterconnection: boolean
}

// Une section d'accordéon du récapitulatif
export interface AnalysisGroup {
  id: string
  title: string
  signalements: MassAffectationSignalement[]
}