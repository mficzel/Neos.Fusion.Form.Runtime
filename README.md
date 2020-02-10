# Fusion Form Runtime

Pure fusion form runtime with afx support!

## !!! This is experimental and may turn out to be a totally stupid idea!!!

## Define a form with validation and finishing actions entirely in fusion:

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form:MultiStepForm {

        data = Neos.Fusion:DataStructure {
            firstName = "aaaa"
        }

        identifier = "exampleForm2"

        steps {

            first {
                renderer = afx`
                    <fieldset>
                        <legend>name</legend>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="firstName" label="First Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="lastName" label="Last Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators = Neos.Fusion:DataStructure {
                    firstName {
                        1 {
                            class = '\\Neos\\Flow\\Validation\\Validator\\NotEmptyValidator'
                        }
                    }
                    lastName {
                        1 {
                            class = '\\Neos\\Flow\\Validation\\Validator\\NotEmptyValidator'
                        }
                    }
                }
            }

            second {
                renderer = afx`
                    <fieldset>
                        <legend>address</legend>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="street" label="Street">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                        <Neos.Fusion.Form:Neos.BackendModule.FieldContainer field.name="city" label="City">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:Neos.BackendModule.FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators = Neos.Fusion:DataStructure {
                    street {
                        1 {
                            class = '\\Neos\\Flow\\Validation\\Validator\\NotEmptyValidator'
                        }
                    }
                    city {
                        1 {
                            class = '\\Neos\\Flow\\Validation\\Validator\\NotEmptyValidator'
                        }
                    }
                }
            }

            last {
                renderer = afx`
                    <h1>Confirm to submit {data.firstName} {data.lastName} from {data.city}, {data.street}</h1>
                    <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                `
            }
        }

        action {
            message = Neos.Fusion.Form.Runtime:Action.Message {
                content = afx`<h1>Thank you {data.firstName} {data.lastName} from {data.city}, {data.street}</h1>`
            }
        }
    }
}
``` 
