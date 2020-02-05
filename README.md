# Fusion Form Runtime

Pure fusion form runtime with afx support!

## !!! This is experimental and may turn out to be a totally stupid idea!!!

## Define a form with validation and finishing actions entirely in fusion:

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form.Runtime:MultiStepForm {

        identifier = "exampleForm2"

        data = Neos.Fusion:DataStructure {
            firstName = "Max"
            lastName = "Mustermann"
        }

        steps {

            first {
                content = afx`
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
                validator {
                    firstName = Neos.Fusion.Form.Runtime:Validator.NotEmpty
                    lastName = Neos.Fusion.Form.Runtime:Validator.NotEmpty
                }
            }

            second {
                content = afx`
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
                
                validator {
                    street =  Neos.Fusion.Form.Runtime:Validator.NotEmpty
                    city =  Neos.Fusion.Form.Runtime:Validator.NotEmpty
                }
            }

            last {
                content = afx`<h1>Thank you {form.data.firstName} {form.data.lastName} from {form.data.city}, {form.data.street}</h1>`
            }
        }
    }
    
}
``` 
