# Fusion Form Runtime

Pure fusion form runtime with afx support!

## !!! This is experimental and may turn out to be a totally stupid idea!!!

## Pain points i try to address with this

- Ist is hard to get access to node-data in finishers (email address, redirect targets)
- It is hard to configure parts of the form from node-data (disable steps, disable parts of the form)
- It is tedious to render custom markup for (grids, fieldTypes, content between fields)
- Form support only a flat list of arguments and no domain objects
- Forms are very hard to extend as you had to deal with settings.yaml, form.yaml and Fluid  

## Possible new pain points of this

- Validation and Types are defined seperately from rendering
- Every property needs at least one validator or a type

## Define a form with validation and finishing actions entirely in fusion:

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form:MultiStepForm {

        #
        # The form identifier, this is used as argument namespace for the 
        # form to seperate the values form other forms 
        #
        identifier = "exampleForm2"

        #
        # The initial `data` of the form, will be used to prefill the input
        # elements. During steps the data is serialized and signed with a hmac
        #
        data = Neos.Fusion:DataStructure 

        #
        # The collection of form steps, the collection defines the order
        # of the steps and can disable single steps. All steps have to be 
        # submitted before the actions are called
        #
        steps = Neos.Fusion.Form:MultiStepForm.StepCollection {

            # 
            # A form step provides a `renderer` and optional `validators` 
            # and `types`. 
            #
            # HINT: Only value that have `validators` or a `type` are Added to the 
            # form state. Other values are ignored as they may easyly be manipulated
            # from outside and cannot be trusted. 
            #
            # This may still change and also allow all values that have __trustedProperties
            #
            first = Neos.Fusion.Form:MultiStepForm.Step {
                renderer = afx`
                    <fieldset>
                        <legend>name</legend>
                        <Neos.Fusion.Form:FieldContainer field.name="firstName" label="First Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:FieldContainer>
                        <Neos.Fusion.Form:FieldContainer field.name="lastName" label="Last Name">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators {
                    firstName.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                    lastName.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                }
            }

            second {
                renderer = afx`
                    <fieldset>
                        <legend>address</legend>
                        <Neos.Fusion.Form:FieldContainer field.name="street" label="Street">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:FieldContainer>
                        <Neos.Fusion.Form:FieldContainer field.name="city" label="City">
                            <Neos.Fusion.Form:Input />
                        </Neos.Fusion.Form:FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button field.name="__targetStep" field.value="first">Back</Neos.Fusion.Form:Button>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `

                validators {
                    street.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                    city.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                }
            }
            
            third {
                renderer = afx`
                    <fieldset>
                        <legend>file</legend>
                        <Neos.Fusion.Form:FieldContainer field.name="file" label="Street">
                            <Neos.Fusion.Form:Upload />
                        </Neos.Fusion.Form:FieldContainer>
                    </fieldset>
                    <div>
                        <Neos.Fusion.Form:Button field.name="__targetStep" field.value="second">Back</Neos.Fusion.Form:Button>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `
                
                validators {
                    file.notEmpty.identifier = 'Neos.Flow:NotEmpty'
                    file.limitFileTypes {
                        identifier = 'Neos.Fusion.Form.Runtime:UploadedFile'
                        options {
                            allowedExtensions = ${['svg', 'txt', 'jpg']}
                        }
                    }

                }
            }

            #
            # A confirmation step can be added as any other step. The submitted
            # `data` is available and can be used for rendering. 
            #
            # HINT: Be aware that this is untrusted data that should be escaped
            #
            confirmation {
                renderer = afx`
                    <h1>Confirm to submit {String.htmlSpecialChars(data.firstName + ' ' + data.lastName)} from {String.htmlSpecialChars( data.city + ', ' + data.street)}</h1>
                    <div>
                        <Neos.Fusion.Form:Button field.name="__targetStep" field.value="second">Back</Neos.Fusion.Form:Button>
                        <Neos.Fusion.Form:Button>Submit</Neos.Fusion.Form:Button>
                    </div>
                `
            }
        }

        #
        # The actions are evaluated after all steps were sucessfully submitted
        # Each action has an `identifier` that is used to identify the ActionHandler 
        # and an `options` DataStructure. 
        #
        # The identifier can be a fully qualified classname or a PackageName:IdentifierName
        # that will be converted to a className
        #
        actions = Neos.Fusion:DataStructure {
        
            #
            # The message action accepts a single option `message` that is returned directly
            # You should be careful with this as it will not prevent reloading and calling
            # all action n-times 
            # 
            message {
                identifier = 'Neos.Fusion.Form.Runtime:Message'
                options.message = afx`<h1>Thank you {String.htmlSpecialChars(data.firstName + ' ' + data.lastName)}</h1>`
            }

            #
            # EMail action with attachment and multipart email support
            # the Neos.Swiftmailer package is required
            # 
            # HINT: Be aware that this is untrusted data that should be escaped 
            #
            email {
                identifier = 'Neos.Fusion.Form.Runtime:Email'
                options {
                    senderAddress = ${q(node).property('mailFrom')}
                    recipientAddress = ${q(node).property('mailTo')}

                    subject = ${q(node).property('mailSubject')}
                    
                    text = afx`Thank you {String.htmlSpecialChars(data.firstName + ' ' + data.lastName)} from {String.htmlSpecialChars(data.city)}, {String.htmlSpecialChars(data.street)}`
                    html = afx`<h1>Thank you {String.htmlSpecialChars(data.firstName + ' ' + data.lastName)} {data.lastName}</h1><p>from {String.htmlSpecialChars(data.city)}, {String.htmlSpecialChars(data.street)}</p>`
                    
                    attachments {
                        fromUpload = ${data.file}
                        fromPath = "resource://Form.Test/Private/Fusion/Test.translation.csv"
                        fromData {
                            content = ${Json.stringify(data)}
                            name = 'data.json'
                        }
                    }
                }
            }
            
            #
            # Log action, can use all PSR Logger the LoggerFactory can create
            #
            log {
                identifier = 'Neos.Fusion.Form.Runtime:Log'
                options {
                    logger = 'systemLogger'
                    level = 'info'
                    message = 'Form was submitted'
                    context = ${data}
                }
            }

            #
            # Redirect action, sends a redirect to a thankyou page. This allows
            # to place generic content on that page and prevents reloading and
            # retriggering the actions 
            #
            redirect {
                identifier = 'Neos.Fusion.Form.Runtime:Redirect'
                options {
                    uri = Neos.Neos:NodeUri {
                        node = ${q(node).property('thankyou')}
                    }
                }
            }
        }
    }
}
``` 

## A more realistic scenario that uses presentational components will look more like this

```
prototype(Form.Test:Content.ExampleForm) < prototype(Neos.Neos:ContentComponent) {

    renderer = Neos.Fusion.Form:MultiStepForm {

        identifier = "exampleForm3"
        data = Neos.Fusion:DataStructure 
        steps {
            person {
                renderer = Vendor.Site:Component.Organism.CallbackForm.Step.Person
                validators = Vendor.Site:Component.Organism.CallbackForm.Step.Person.Validators
            }

            address {
                renderer = Vendor.Site:Component.Organism.CallbackForm.Step.Address
                validators = Vendor.Site:Component.Organism.CallbackForm.Step.Address.Validators
            }
            
            file {
                renderer = Vendor.Site:Component.Organism.CallbackForm.Step.File
                types = Vendor.Site:Component.Organism.CallbackForm.Step.File.Types
            }

            confirmation {
                renderer = renderer = Vendor.Site:Component.Organism.CallbackForm.Step.Confirmation {
                    data = ${data}
                }
            }
        }

        actions = Neos.Fusion:DataStructure {

            email {
                identifier = 'Neos.Fusion.Form.Runtime:Email'
                options {
                    senderAddress = ${q(node).property('mailFrom')}
                    recipientAddress = ${q(node).property('mailTo')}
                    subject = ${q(node).property('mailSubject')}
                    text = Vendor.Site:Component.Organism.CallbackForm.Action.Email.Text {
                        data =  ${data}
                    }                    
                    html = Vendor.Site:Component.Organism.CallbackForm.Action.Email.Html {
                        data =  ${data}
                    }
                    attachments {
                        fromUpload = ${data.file}
                    }
                }
            }
            
            redirect {
                identifier = 'Neos.Fusion.Form.Runtime:Redirect'
                options {
                    uri = Neos.Neos:NodeUri {
                        node = ${q(node).property('thankyou')}
                    }
                }
            }
        }
    }
}
``` 
